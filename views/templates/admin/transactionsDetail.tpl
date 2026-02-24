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
{$messagerefundDatafast}
</div>
	<div class="row">
		<div class="col-md-12">
		<div class="panel">
			<h3>
				DETALLE DE TRANSACCIÓN 
					{if $payment_type == 'DB'}
						{if $status == '1'}
							<a href="{$href_refund}" class="btn btn-danger">Reversar</a>
						{/if}
						{if $status == '2'}
							<a class="btn btn-info">Reversado</a>
						{/if}
					{/if}
			</h3>
			<table class="table">
			<tbody>
				<tr>
					<td>Orden:</td>
					<td>{$cart_id}</td>
				</tr>
				<tr>
					<td>Fecha Ejecución:</td>
					<td>{$updated_at}</td>
				</tr>
				<tr>
					<td>Id Transacción:</td>
					<td>{$transaction_id}</td>
				</tr>
				<tr>
					<td>Tipo Transacción:</td>
					<td>{$payment_type}</td>
				</tr>
				<tr>
					<td>Monto:</td>
					<td>{$amount}</td>
				</tr>
				<tr>
					<td>Respuesta Botón:</td>
					<td>{$result_code}</td>
				</tr>
				<tr>
					<td>Respuesta Banco:</td>
					<td>{$response}</td>
				</tr>
				<tr>
					<td>Descripción de Respuesta:</td>
					<td>{$extended_description}</td>
				</tr>
				<tr>
					<td>Lote:</td>
					<td>{$batch_no}</td>
				</tr>
				<tr>
					<td>Referencia:</td>
					<td>{$reference_no}</td>
				</tr>
				<tr>
					<td>Adquirente:</td>
					<td>{$acquirer_code}</td>
				</tr>
				<tr>
					<td>Autorización:</td>
					<td>{$auth_code}</td>
				</tr>
				<tr>
					<td>Monto Total:</td>
					<td>{$total_amount}</td>
				</tr>
				<tr>
					<td>Interés:</td>
					<td>{$interest}</td>
				</tr>
				<tr>
					<td>Trama Completa:</td>
					<td>{$response_json}</td>
				</tr>
				</tbody>
			</table>
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