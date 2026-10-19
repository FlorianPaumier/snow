<?php

declare(strict_types=1);

use App\Actions\GenerateContractPdf;
use App\Enums\PaymentMethod;
use App\Enums\PaymentSlot;
use App\Jobs\GenerateContractDocumentPdf;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

/**
 * A contract with two services, a paid deposit and an unpaid first installment.
 */
function documentContract(User $user): Contract
{
    $client = Client::factory()->forOrganization($user->organization)->create([
        'firstName' => 'Jean',
        'lastName' => 'Tremblay',
    ]);
    $contract = Contract::factory()->forClient($client)->create(['season' => '2026-2027']);
    $contract->services()->createMany([
        ['label' => 'Déneigement entrée', 'amount' => 42500, 'position' => 1],
        ['label' => 'Stationnement', 'amount' => 15000, 'position' => 2],
    ]);
    $contract->payments()->createMany([
        ['slot' => PaymentSlot::SLOT_DEPOSIT, 'amount' => 15000, 'paid' => true, 'method' => PaymentMethod::PAYMENT_VIREMENT, 'paid_at' => now()],
        ['slot' => PaymentSlot::SLOT_FIRST_INSTALLMENT, 'amount' => 42500, 'paid' => false, 'method' => null, 'paid_at' => null],
    ]);

    return $contract;
}

function fakeDocumentDisk(): FilesystemAdapter
{
    return Storage::fake((string) config('filesystems.default'));
}

it('renders the printable contract document', function (): void {
    $user = User::factory()->create();
    $contract = documentContract($user);

    $this->actingAs($user)
        // The document follows the visitor's locale; pin English so the
        // assertions are deterministic (the middleware defaults to fr)...
        ->withUnencryptedCookie('locale', 'en')
        ->get(route('contracts.print', $contract))
        ->assertOk()
        ->assertSee('Contract summary')
        ->assertSee($user->organization->name)
        ->assertSee('Jean Tremblay')
        ->assertSee('Déneigement entrée')
        ->assertSee('Paid on')
        ->assertSee('Due')
        ->assertSee($contract->client->clientNumber);
});

it('requires authentication to view the document', function (): void {
    $contract = documentContract(User::factory()->create());

    $this->get(route('contracts.print', $contract))->assertRedirect(route('login'));
});

it('does not find another organization contract on the document routes', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $contract = documentContract($other);

    $this->actingAs($user)->get(route('contracts.print', $contract))->assertNotFound();
    $this->actingAs($user)->get(route('contracts.pdf.download', $contract))->assertNotFound();
    $this->actingAs($user)->post(route('contracts.pdf.generate', $contract))->assertNotFound();
});

it('queues PDF generation when none is stored yet', function (): void {
    Queue::fake();
    $user = User::factory()->create();
    $contract = documentContract($user);

    $this->actingAs($user)
        ->post(route('contracts.pdf.generate', $contract))
        ->assertRedirect()
        ->assertSessionHas('status');

    Queue::assertPushed(
        GenerateContractDocumentPdf::class,
        fn (GenerateContractDocumentPdf $job): bool => $job->contractId === $contract->id,
    );
});

it('sends the browser to the download when a PDF is already stored', function (): void {
    Queue::fake();
    $user = User::factory()->create();
    $contract = documentContract($user);
    fakeDocumentDisk()->put(GenerateContractPdf::pdfPath($contract->id), 'PDF');

    $this->actingAs($user)
        ->withHeaders(['X-Inertia' => 'true'])
        ->post(route('contracts.pdf.generate', $contract))
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', route('contracts.pdf.download', $contract));

    Queue::assertNothingPushed();
});

it('streams the stored PDF', function (): void {
    Storage::fake((string) config('filesystems.default'));
    $user = User::factory()->create();
    $contract = documentContract($user);
    fakeDocumentDisk()->put(GenerateContractPdf::pdfPath($contract->id), '%PDF-1.4 fake');

    $this->actingAs($user)
        ->get(route('contracts.pdf.download', $contract))
        ->assertOk()
        ->assertDownload(sprintf('Contrat-%s-%s.pdf', $contract->season, $contract->client->clientNumber));
});

it('returns 404 when the PDF has not been generated', function (): void {
    Storage::fake((string) config('filesystems.default'));
    $user = User::factory()->create();
    $contract = documentContract($user);

    $this->actingAs($user)
        ->get(route('contracts.pdf.download', $contract))
        ->assertNotFound();
});

it('reports queued PDF readiness without streaming a download or queuing again', function (): void {
    Queue::fake();
    $user = User::factory()->create();
    $contract = documentContract($user);
    $disk = fakeDocumentDisk();

    $this->actingAs($user)->postJson(route('contracts.pdf.generate', $contract))
        ->assertAccepted()->assertExactJson(['ready' => false]);
    Queue::assertPushed(GenerateContractDocumentPdf::class, 1);
    $this->getJson(route('contracts.pdf.download', $contract))
        ->assertOk()->assertExactJson(['ready' => false])->assertHeader('Cache-Control', 'no-store, private');
    Queue::assertPushed(GenerateContractDocumentPdf::class, 1);

    $disk->put(GenerateContractPdf::pdfPath($contract->id), '%PDF-test');
    $this->getJson(route('contracts.pdf.download', $contract))->assertOk()->assertExactJson(['ready' => true]);
    $this->postJson(route('contracts.pdf.generate', $contract))->assertOk()->assertExactJson(['ready' => true]);
    Queue::assertPushed(GenerateContractDocumentPdf::class, 1);
});

it('protects JSON PDF readiness from guests and other organizations', function (): void {
    $owner = User::factory()->create();
    $contract = documentContract($owner);
    $this->getJson(route('contracts.pdf.download', $contract))->assertUnauthorized();
    $other = User::factory()->create();
    $this->actingAs($other)->getJson(route('contracts.pdf.download', $contract))->assertNotFound();
    $this->postJson(route('contracts.pdf.generate', $contract))->assertNotFound();
});
