<style>
td {
  white-space: normal !important; 
  word-wrap: break-word;  
}
table {
  table-layout: fixed;
} 
</style> 
<div class="container">
	<div class="row">
		<div class="col-md-12"> 
		</div>
		<div class="row">
			<div class="col-md-12">
				<div class="panel">
					<h3>
						RECUPERAR TRANSACCIONES
					</h3> 
					<p>	
						<form method="post" >
							<div class="form2bc">
								<p>			
									<label class="dflb" for="trxs">Transacciones: </label>
									<br>
									<input id="trxs" name="trxs" type="text" placeholder="Id de Transacciones" autocomplete="off"
											required>
									<button type="submit" class="btn btn-primary" name="sbmttrxs">Guardar</button>
								</p> 
							</div>   
						</form> 
					</p>  
					{section name=co loop=$success}
						<div class="p-3 mb-2 bg-success text-white"> 
							<p>
								<strong>Transacción {$success[co]} recuperada correctamente.</strong>  
							</p> 
						</div> 
					{/section}
					{section name=co loop=$errorsTrxs}
						<div class="p-3 mb-2 bg-danger text-white"> 
							<p>
								<strong>Error la transacción {$errorsTrxs[co]} no se pudo encontrar.</strong> 
							</p> 
						</div> 
				   {/section}
				   {section name=co loop=$duplicates}
					   <div class="p-3 mb-2 bg-warning text-white"> 
						   <p> 
							   <strong>Error transacción {$duplicates[co]} repetida. La transacción ya existe en los registros de plugin.</strong> 
						   </p> 
					   </div> 
					{/section}
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
 

