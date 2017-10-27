<html>
<head>

</head>
<body>
    <h1>INNO<span style="color:red">V</span>AGENDA</h1>
    <div>Prezado usuario,</div>
    <p>
        Para mudar a senha, clica aqui: <a href="{{ $link = url('password/reset', $data['token']).'?email='.urlencode($data['mail']) }}"> click! </a>
    </p>
    <div>Bom trabalho!</div>
    <br/>
    <div>At.te</div>
    <div>InnoVAgenda Time</div>
</body>
</html>