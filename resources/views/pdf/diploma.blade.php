<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  @page { size: A4 landscape; margin: 10mm; }
  html, body { width: 100%; height: 100%; }
  body { font-family: 'Roboto', 'DejaVu Sans', sans-serif; }

  @foreach(($font_faces ?? []) as $f)
    @font-face {
      font-family: '{{ $f['family'] }}';
      font-style: {{ $f['style'] }};
      font-weight: {{ $f['weight'] }};
      src: url('{{ $f['path'] }}') format('truetype');
    }
  @endforeach

  .diploma-bg {
    width: 100%;
    height: 100%;
    background-color: #C6E8ED;
    border-collapse: collapse;
  }
  .diploma-bg td { padding: 0; }
  .pad-top    { height: 1%; padding: 12mm 22mm 0 22mm !important; vertical-align: top; }
  .pad-mid    { vertical-align: middle; padding: 0 22mm !important; }
  .pad-bottom { height: 1%; padding: 0 22mm 12mm 22mm !important; vertical-align: bottom; }

  .logo-wrap { text-align: right; }
  .logo-wrap img { width: 40mm; }

  .title {
    font-family: 'Playfair Display', 'DejaVu Serif', serif;
    font-size: 56pt;
    font-weight: 500;
    color: #1161A8;
    text-align: center;
    line-height: 0.6;
    letter-spacing: -3px;
    margin-top: 2mm;
  }
  .title span { margin-left: 220px; }

  .sep  { border: none; border-top: 2px solid #1161A8; width: 100%; }
  .sep2 { border: none; border-top: 2px solid #1161A8; width: 100%; margin-bottom: 6mm; margin-top:2mm;}

  .doctor-name {
    font-family: 'Playfair Display', 'DejaVu Serif', serif;
    font-size: 22pt;
    text-transform: uppercase;
    font-style: italic;
    color: #1161A8;
    text-align: center;
    margin: 0;
  }

  .main-text {
    font-family: 'Roboto', 'DejaVu Sans', sans-serif;
    font-size: 11pt;
    font-weight: 400;
    color: #1161A8;
    text-align: center;
      margin-top: 2mm;
      margin-bottom: 2mm;
  }
  .main-text .workshop-title { font-weight: 700; font-size: 16pt; line-height: 0.8; }
  .main-text .credits        { font-weight: 700; font-size: 13pt; line-height: 0.8; }
  .main-text .credits-ref    { font-size: 11pt; font-weight: 400; }

  .signatures-wrap { text-align: right; }
  .signer {
    display: inline-block;
    vertical-align: top;
    text-align: center;
    margin-left: 18mm;
  }
  .signer:first-child { margin-left: 0; }
  .sig-role {
    font-family: 'Roboto', 'DejaVu Sans', sans-serif;
    font-size: 9pt;
    font-weight: 700;
    color: #000;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    text-align: center;
  }
  .sig-name {
    font-family: 'Roboto', 'DejaVu Sans', sans-serif;
    font-size: 9pt;
    font-weight: 700;
    color: #000;
    letter-spacing: 0.04em;
    text-align: center;
    margin-top: 0;
  }
  .sig-img {
    height: 18mm;
    max-width: 50mm;
    display: block;
    margin: -10px auto 0 auto;
  }
</style>
</head>
<body>

<table class="diploma-bg" cellspacing="0" cellpadding="0">
  <tr>
    <td class="pad-top">
      <div class="logo-wrap">
        <img src="{{ $logo_path }}" alt="Logo" />
      </div>
      <div class="title">
        Certificat de<br>
        <span>participare</span>
      </div>
    </td>
  </tr>
  <tr>
    <td class="pad-mid">
        <hr class="sep" style="margin-top: 6mm;"/>
      <div class="doctor-name">{{ $doctor_name }}</div>
        <hr class="sep" />
      <div class="main-text">
        a participat la atelierul de lucru cu titlul<br>
        <span class="workshop-title">„{{ $workshop_title }}"</span><br>
        organizat la {{ $location }} în perioada {{ $period }}.<br>
        @if($credits)
          <br>
          <span class="credits">COLEGIUL MEDICILOR DIN ROMÂNIA ACORDĂ PENTRU ACEST ATELIER UN NUMĂR DE {{ $credits }} CREDITE EMC.</span><br>
          @if($cmr_address)
            <span class="credits-ref">(adresa Nr. {{ $cmr_address }})</span>
          @endif
        @endif
      </div>
    </td>
  </tr>
  <tr>
    <td class="pad-bottom">
      <hr class="sep2" />
      @if(count($signers) > 0)
      <div class="signatures-wrap">
        @foreach($signers as $s)
        <div class="signer">
          <div class="sig-role">DIRECTOR DE CURS</div>
          <div class="sig-name">{{ $s['name'] }}</div>
          @if(!empty($s['signature']))
            <img class="sig-img" src="{{ $s['signature'] }}" alt="{{ $s['name'] }}" />
          @endif
        </div>
        @endforeach
      </div>
      @endif
    </td>
  </tr>
</table>

</body>
</html>
