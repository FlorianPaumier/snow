@props(['url'])
<tr>
<td class="header" style="background-color: #003437; border-radius: 12px 12px 0 0;">
<a href="{{ $url }}" style="display: inline-block; padding: 24px 32px;">
@if (trim($slot) === 'Laravel')
<span style="color: #ffffff; font-size: 20px; font-weight: 700; letter-spacing: -0.01em; text-decoration: none;">{{ config('app.name') }}</span>
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
<tr>
<td style="background-color: #00a7a9; font-size: 0; line-height: 0; height: 3px;">&nbsp;</td>
</tr>
