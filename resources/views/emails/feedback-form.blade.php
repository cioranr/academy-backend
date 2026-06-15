<x-mail::message>
# Bună ziua, {{ $firstName }}!

Mulțumim pentru participarea la evenimentul **{{ $event->title }}**.

Vă rugăm să completați formularul de feedback pentru a ne ajuta să îmbunătățim evenimentele noastre viitoare.

**Completând formularul, veți primi automat diploma de participare.**

<x-mail::button :url="$feedbackUrl" color="monza">
Completează formularul de feedback
</x-mail::button>

> Formularul poate fi completat o singură dată. Diploma va fi generată și trimisă automat după completare.

---

Cu stimă,
**Echipa Monza Ares Academy**
</x-mail::message>
