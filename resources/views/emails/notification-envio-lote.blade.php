<html>
<head>

</head>
<body>
    <h1><font color="#B22222"><b>B</b></font>ravo - Tax Calendar</h1>
    <div>Envio automático dos arquivos referente às obrigações fiscais em {{ $data['data'] }}.</div>
    <div>
    @foreach($data['linkDownload'] as $key => $el)
            <a href="{{ $el }}">Download</a><br />
    @endforeach
    </div>

    <br/>
    <div>Não responder, isto é uma mensagem automática.</div><br>
    <div>At.te</div>
    <div>Bravo - Tax Calendar Time</div>
    <hr/>
    <br/>
    <a href="http://innovagenda.innovative.com.br/agenda">Link Acesso Bravo - Tax Calendar CLOUD</a>
    <hr/>
</body>
</html>