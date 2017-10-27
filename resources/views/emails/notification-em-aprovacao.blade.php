<html>
<head>

</head>
<body>
    <h1>INNO<span style="color:red">V</span>AGENDA</h1>

    Prezado Analista Líder,

    As seguintes atividades estão necessitando da sua atenção para a aprovação:
    <br/>
    <p>
      @foreach ($data['messageLines'] as $messageLine)
        {{ $messageLine }}<br>
      @endforeach
    </p>
    <div>Obrigado pela atenção.</div>
    <br/>
    <div>At.te</div>
    <div>InnoVagenda Time</div>
    <hr/><br/>
    <a href="http://innovagenda.innovative.com.br/agenda">Link Acesso INNOVAGENDA CLOUD</a>
</body>
</html>