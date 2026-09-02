@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
@php
    $buttonColors = [
        'primary' => '#e8651d',
        'success' => '#2f9e63',
        'error' => '#c93b3b',
    ];
    $buttonBg = $buttonColors[$color] ?? $buttonColors['primary'];
@endphp
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
<a href="{{ $url }}" class="button button-{{ $color }}" target="_blank" rel="noopener" style="background-color: {{ $buttonBg }}; border-color: {{ $buttonBg }}; color: #ffffff;">{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
