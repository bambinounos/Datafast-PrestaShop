<style>
td {
  white-space: normal !important; 
  word-wrap: break-word;  
}
table {
  table-layout: fixed;
}

</style>
<script type="text/javascript" defer> 
	function testing(pro, proName){   
		var templateUrl = '/modules/datafast/api/ajax-test-call.php';
		logFetch(templateUrl+'?pro='+pro).then(response=>{
			let idResult=(pro=='1' || pro=='2')?('resultProd'+pro):'resultTest'+pro;
			let env = (pro=='1' || pro=='2')?('Producción'+' '+proName):'Pruebas' + ' ' +proName ;
			let resultJson = JSON.parse(response,true);
			let isError = resultJson.error!=null;
			let textByType = isError?`Error al Conectarse con el API de `+env+` del botón de pago. Error: `:
			`Conexión exitosa con el API `+env;
			let isWarning = isError?false:resultJson.result.code!="000.200.100";
			if(isWarning) textByType += " Con una Advertencia: ";
			let text = isError? resultJson.error:isWarning?resultJson.result.description:'';
			let type=isError?'danger':isWarning?'warning':'success';
			console.log(resultJson)
			$('#'+idResult).html(`
			<div class="p-3 mb-2 bg-`+type+` text-white"> 
				<p>
					<strong>`+textByType+`</strong>
					`+text+`
				</p> 
			</div>`); 
		}); 
	}
	async function logFetch(url) {
		try {
		const response = await fetch(url, {
			method: 'GET' 
		});
		return await response.text();
		}
		catch (err) {
		alert('Ocurrio un error cuando se intento hacer el Test.');
		console.log('error', err);
		}
	}
</script>
<div class="container">
<div class="row">
		<div class="col-md-12"> 
</div>
	<div class="row">
		<div class="col-md-12">
			<div class="panel">
				<h3>
					INFORMACIÓN Y TEST DE API
				</h3>
				<table class="table">
				<tbody>
					<tr>
						<td>Ip Pública del Servidor:</td>
						<td>{$IPServidor}</td>
						<td></td>
					</tr>
					<tr>
						<td>Ip cliente (Navegador Actual):</td>
						<td>{$IPCliente}</td>
						<td></td>
					</tr>
				</table>
				<p>	
				</p> 
				<div class="formdatabc">		  		
					<div class="form2bc">
							<button type="button" class="btn btn-primary dfa" 
							onclick="testing(0,'(test.oppwa.com)')">Test Producción 1 (test.oppwa.com)</button>
					</div> 
					<div class="row">
						<div class="col-md-12">
							<div id='resultTest0'>
							</div>
						</div>
					</div>  
					<p>	
					</p> 
					<div class="form2bc">
							<button type="button" class="btn btn-primary dfa" 
							onclick="testing(3,'(eu-test.oppwa.com)')">Test Producción 2 (eu-test.oppwa.com)</button>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div id='resultTest3'>
							</div>
						</div>
					</div> 
						<p>	
						</p> 
					<div class="form2bc">
						<button type="button" class="btn btn-primary dfa" 
						onclick="testing(1,'(oppwa.com)')">Test Producción 1 (oppwa.com)</button>
					</div>
						<div class="row">
							<div class="col-md-12">
								<div id='resultProd1'>
								</div>
							</div>
						</div>
						<p>	
						</p> 
					<div class="form2bc">	
						<button type="button" class="btn btn-primary dfa" 
							onclick="testing(2,'(eu-prod.oppwa.com)')">Test Producción 2(eu-prod.oppwa.com)</button>	
					</div>
					<div class="row">
						<div class="col-md-12">
							<div id='resultProd2'>
							</div>
						</div>
					</div>
						<p>	
						</p> 
				</div> 
			</div> 
		</div>
	</div>
				
				<footer class="form-footer">
					<style>
					input[name=url] {
						display: none !important;
					}
					</style>
						
					<br><br><br>
					<a href="{$href_return}" class="btn btn-default">
					<i class="process-icon-back"></i> Regresar</a>
				</footer>
			
			</div>
		</div>
	</div>
</div>
 

