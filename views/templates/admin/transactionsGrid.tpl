    <form method="POST" action="?controller={$smarty.get.controller|escape:'html':'UTF-8'}&configure={$smarty.get.configure|escape:'html':'UTF-8'}&token={$smarty.get.token|escape:'html':'UTF-8'}&viewTransactions">
        <div class="panel" id="fieldset_0">
            <div class="panel-heading">
                <i class="icon-eye"></i> Consulta de Transacciones
            </div>
                <input type="hidden" name="subaction" value="viewData" />
                <table>
                    <tr>
                        <td style="width: 80px; text-align: center;">Año</td>
                        <td style="width: 150px">
                            <select name='year'>
                               {$allYearOptions nofilter}
                            </select>
                        </td>
                        <td style="width: 80px; text-align: center;">Mes</td>
                        <td style="width: 150px">
                            <select name='month'>
                               {$allMonthOptions nofilter}
                            </select>
                        </td>
						<td style="width: 80px; text-align: center;">Orden</td>
                        <td style="width: 150px">
                            <input name="txtOrden" id="txtOrden" class="form-control" value="{$txtOrden|escape:'html':'UTF-8'}">
                        </td>
						<td style="width: 80px; text-align: center;">Id Trx</td>
                        <td style="width: 150px">
                            <input name="txtIdTrx" id="txtIdTrx" class="form-control" value="{$txtIdTrx|escape:'html':'UTF-8'}">
                            </select>
                        </td>
                        <td class="actions" style="padding-left: 20px">
                            <span class="pull-right">
                                <button type="submit" class="btn btn-default" >
                                <i class="icon-search"></i> Visualizar
                                </button>
                            </span>
                        </td>
                    </tr>
                </table>
				</div>
				<div class="table-responsive-row clearfix">
                <table class="table datafast">
				<thead>
				<tr class="nodrag nodrop">
				<th><span class="title_box">Id Transacción</span></th>
				<th><span class="title_box">Orden</span></th>
				<th><span class="title_box">Fecha Ejecución</span></th>
				<th><span class="title_box">Tipo Trx</span></th>
				<th><span class="title_box">Id Trx</span></th>
				<th><span class="title_box">Resp. Botón</span></th>
				<th><span class="title_box">Resp.  Banco</span></th>
				<th><span class="title_box">Descripción de Respuesta</span></th>
				<th><span class="title_box">Lote</span></th>
				<th><span class="title_box">Referencia</span></th>
				<th><span class="title_box">Adq.</span></th>
				<th><span class="title_box">Aut.</span></th>
				<th><span class="title_box">Monto</span></th>
				<th><span class="title_box">Interés</span></th>
				<th><span class="title_box">Monto Total</span></th>
				<th><span class="title_box">Estado</span></th>
				<th><span class="title_box">Visualizar</span></th>
				</tr>
				</thead>
				<tbody>
				 {section name=co loop=$data}
				 							<tr class=" odd">
                                            		<td>{$data[co].id_transaction|escape:'html':'UTF-8'}</td>
													<td>{$data[co].cart_id|escape:'html':'UTF-8'}</td>
													<td>{$data[co].updated_at|escape:'html':'UTF-8'}</td>
													<td>{$data[co].payment_type|escape:'html':'UTF-8'}</td>
													<td>{$data[co].transaction_id|escape:'html':'UTF-8'}</td>
													<td>{$data[co].result_code|escape:'html':'UTF-8'}</td>
													<td>{$data[co].response|escape:'html':'UTF-8'}</td>
													<td>{$data[co].extended_description|escape:'html':'UTF-8'}</td>
													<td>{$data[co].batch_no|escape:'html':'UTF-8'}</td>
													<td>{$data[co].reference_no|escape:'html':'UTF-8'}</td>
													<td>{$data[co].acquirer_code|escape:'html':'UTF-8'}</td>
													<td>{$data[co].auth_code|escape:'html':'UTF-8'}</td>
													<td>{$data[co].amount|escape:'html':'UTF-8'}</td>
													<td>{$data[co].interest|escape:'html':'UTF-8'}</td>
													<td>{$data[co].total_amount|escape:'html':'UTF-8'}</td>
													<td>{$data[co].status_name|escape:'html':'UTF-8'}</td>
													<td class="text-right">
														<div class="btn-group-action">
															<div class="btn-group pull-right">
																	<a href="{$data[co].href_detail|escape:'html':'UTF-8'}" class="btn btn-default" title="Ver">
																	<i class="icon-search-plus"></i> Ver
																	</a>

															</div>
														</div>
													</td>
											</tr>
                {/section}
				</tbody>
				</table>
                </div>
				<br/>

                <div class="panel-footer">
				<a href="{$href_return|escape:'html':'UTF-8'}" class="btn btn-default">
				<i class="process-icon-back"></i> Regresar</a>
                </div>
            </div>

        </form>