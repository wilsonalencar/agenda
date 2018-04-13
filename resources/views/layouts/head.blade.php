<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Agenda Fiscal</title>


<!-- BOOTSTRAP CSS -->
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/css/bootstrap.min.css">
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.6.3/css/font-awesome.min.css">
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.0/css/bootstrap-datepicker.css">
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-modal/2.2.6/css/bootstrap-modal.min.css">
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-switch/3.3.2/css/bootstrap3/bootstrap-switch.css">
<!-- DataTables CSS -->
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/datatables/1.10.12/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/datatables-tabletools/2.1.5/css/TableTools.min.css">
<link rel="stylesheet" href="//cdn.datatables.net/buttons/1.1.2/css/buttons.dataTables.min.css">
<!-- Select2 CSS-->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.2/css/select2.min.css" rel="stylesheet" />
<!-- Calendar CSS-->
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/fullcalendar/2.2.7/fullcalendar.min.css"/>
<!-- Highcharts 5.0.9 CSS -->
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highcharts/5.0.9/css/highcharts.css">
<!-- CUSTOM CSS -->
<link rel="stylesheet" href="{{ URL::to('/') }}/assets/css/custom.css">
<!-- Smart Menus CSS -->
<link rel="stylesheet" href="{{ URL::to('/') }}/assets/css/sm-core-css.css" />
<link rel="stylesheet" href="{{ URL::to('/') }}/assets/css/sm-clean/sm-clean.css" />


<!-- JQUERY -->
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<!-- BOOTSTRAP -->
<script src="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/js/bootstrap.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.0/js/bootstrap-datepicker.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.0/locales/bootstrap-datepicker.pt.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-modal/2.2.6/js/bootstrap-modal.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-modal/2.2.6/js/bootstrap-modalmanager.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-switch/3.3.2/js/bootstrap-switch.min.js"></script>
<!-- DataTables JS -->
<script src="//cdnjs.cloudflare.com/ajax/libs/datatables/1.10.12/js/jquery.dataTables.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/datatables-tabletools/2.1.5/js/TableTools.min.js"></script>
<script src="//cdn.datatables.net/buttons/1.1.2/js/dataTables.buttons.min.js"></script>
<script src="//cdn.datatables.net/buttons/1.1.2/js/buttons.html5.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.20/pdfmake.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.20/vfs_fonts.js"></script>
<!-- select2 JS-->
<script src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.2/js/select2.min.js"></script>
<!-- Highcharts-->

<?php 
	
	if ($_SERVER['REQUEST_URI'] == '/aprovacao') {

?>

<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/highcharts-3d.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>

<?php 
	} else {

?>
<script src="https://code.highcharts.com/highcharts.js"></script> <!-- Lucas adicionou -->
<script src="//cdnjs.cloudflare.com/ajax/libs/highcharts/4.2.7/highcharts.js"></script>
<script src="https://code.highcharts.com/highcharts-3d.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/highcharts/4.2.7/modules/exporting.js"></script>

<?php } ?> 


<script src="//cdnjs.cloudflare.com/ajax/libs/highcharts/4.2.7/highcharts-more.js"></script>
<!-- Masked Input JS -->
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery.maskedinput/1.4.1/jquery.maskedinput.min.js"></script>
<!-- Calendar -->
<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.9.0/moment.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/fullcalendar/2.2.7/fullcalendar.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/fullcalendar/2.2.7/lang/pt.js"></script>
<!-- Custom JS -->
<script src="{{ URL::to('/') }}/assets/js/custom.js"></script>
<!-- Smart Menus JS -->
<script src="{{ URL::to('/') }}/assets/js/jquery.smartmenus.min.js"></script>

<!--- format moeda -->
<script src="{{ URL::to('/') }}/assets/js/jquery.maskMoney.js"></script>

</head>