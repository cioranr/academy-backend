@component('mail::message')

# Resetare parolă

Salut,

Am primit o solicitare de resetare a parolei contului tău **Monza Ares Academy**.

Apasă pe butonul de mai jos pentru a alege o nouă parolă. Linkul este valabil **60 de minute**.

@component('mail::button', ['url' => $url, 'color' => 'primary'])
Resetează parola
@endcomponent

Dacă nu ai solicitat resetarea parolei, poți ignora acest email în siguranță — contul tău rămâne nemodificat.

Cu stimă,\
**Echipa Monza Ares Academy**

@component('mail::subcopy')
Dacă butonul nu funcționează, copiază și lipește acest link în browser:\
[{{ $url }}]({{ $url }})
@endcomponent

@endcomponent
