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
{$messagerefundDatafast nofilter}
</div>
	<div class="row">
		<div class="col-md-12">
		<div class="panel">
			<h3>
				DETALLE DE TRANSACCIÓN
					{if $payment_type == 'DB'}
						{if $status == '1'}
							<a href="{$href_refund|escape:'html':'UTF-8'}" class="btn btn-danger">Reversar</a>
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
					<td>{$cart_id|escape:'html':'UTF-8'}</td>
				</tr>
				<tr>
					<td>Fecha Ejecución:</td>
					<td>{$updated_at|escape:'html':'UTF-8'}</td>
				</tr>
				<tr>
					<td>Id Transacción:</td>
					<td>{$transaction_id|escape:'html':'UTF-8'}</td>
				</tr>
				<tr>
					<td>Tipo Transacción:</td>
					<td>{$payment_type|escape:'html':'UTF-8'}</td>
				</tr>
				<tr>
					<td>Monto:</td>
					<td>{$amount|escape:'html':'UTF-8'}</td>
				</tr>
				<tr>
					<td>Respuesta Botón:</td>
					<td>{$result_code|escape:'html':'UTF-8'}</td>
				</tr>
				<tr>
					<td>Respuesta Banco:</td>
					<td>{$response|escape:'html':'UTF-8'}</td>
				</tr>
				<tr>
					<td>Descripción de Respuesta:</td>
					<td>{$extended_description|escape:'html':'UTF-8'}</td>
				</tr>
				<tr>
					<td>Lote:</td>
					<td>{$batch_no|escape:'html':'UTF-8'}</td>
				</tr>
				<tr>
					<td>Referencia:</td>
					<td>{$reference_no|escape:'html':'UTF-8'}</td>
				</tr>
				<tr>
					<td>Adquirente:</td>
					<td>{$acquirer_code|escape:'html':'UTF-8'}</td>
				</tr>
				<tr>
					<td>Autorización:</td>
					<td>{$auth_code|escape:'html':'UTF-8'}</td>
				</tr>
				<tr>
					<td>Monto Total:</td>
					<td>{$total_amount|escape:'html':'UTF-8'}</td>
				</tr>
				<tr>
					<td>Interés:</td>
					<td>{$interest|escape:'html':'UTF-8'}</td>
				</tr>
				<tr>
					<td>Trama Completa:</td>
					<td>{$response_json|escape:'html':'UTF-8'}</td>
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
				<a href="{$href_return|escape:'html':'UTF-8'}" class="btn btn-default">
				<i class="process-icon-back"></i> Regresar</a>
			</footer>

		</div>
  	</div>
	</div>
</div>