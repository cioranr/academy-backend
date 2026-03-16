@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/') }}/logo.svg" class="logo" alt="Monza Ares Academy" style="height: 48px; width: auto;">
</a>
</td>
</tr>
