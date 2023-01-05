<p>To begin, select a client. Deselect 'download' if you do not want a csv file.</p>
<p style="font-weight:bold;">If you select not to download, and a notice is displayed through your browser stating the
    "Script is unresponsive" please hit "Continue", it is not frozen.</p>
<?php echo $form->renderFormTag(url_for('compliance_programs/adminReport')) ?>
<ul>
    <?php echo $form ?>
    <li>
        <input type="submit" value="Continue"/>
    </li>
</ul>
</form>

<?php 

	global $_user;
	// $url = 'http://10.0.0.51/public/wms3/ehs_hmi_reportcards?bypass=true&method=getComplianceReportsForUser&user_id=2991515'; //. $_user->getId();
	// $curl = curl_init($url);

	// curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

	// $results = json_decode(curl_exec($curl), true);
	//          echo $results; die;               
	// curl_close($curl);
	$results = json_decode(file_get_contents('https://master.hpn.com/wms3/public/ehs_hmi_reportcards?bypass=true&method=getComplianceReportsForUser&user_id=' . $_user->getId()), true);
	
	if(!empty($results)) {
?>

<div>
	<div><h2>Download EHS/HMI Compliance Report</h2></div>
	<div>Please select a compliance report to download</div>
	<div>
		<select id="download_compliance" style="width: auto;">
			<option value="-1">Select a Compliance report</option>
			<?php foreach($results as $result): ?>
			<option value="<?php echo $result['id']; ?>" data-reportcard-id="<?php echo $result['reportcard_id']; ?>"><?php echo $result['name']; ?> (<?php echo date('m/d/Y', strtotime($result['start_date'])); ?> through <?php echo date('m/d/Y', strtotime($result['end_date'])); ?>)</option>
			<?php endforeach; ?>
		</select>
	</div>
	<div class="error" style="display: none; color: red;">Please select a compliance report</div>
	<div><button class="download-wms3-compliance" type="button" data-user-id="<?php echo $_user->getid(); ?>">Download Report</button></div>
</div>
<script type="text/javascript">
	$(function() {
		$('.download-wms3-compliance').click(function() {
			var can_submit = true;
			var client_id = $('#download_compliance').val();
			var card_id = $('#download_compliance option:selected').data('reportcard-id');
			if(client_id == -1) {
				$('#download_compliance').css({border: '1px solid red'})
				can_submit = false;
				$('.error').show();
			}
			
			if(can_submit) {
				$('.error').hide();
				$('#download_compliance').css({border: '1px solid #cccccc'});
				var user_id = $(this).data('user-id');

				window.location = 'https://master.hpn.com/wms3/public/ehs_hmi_reportcards?bypass=true&action=downloadCompliance&client_id=' + client_id + '&user_id=' + user_id + '&program_id=' + card_id;
			}
		});
	});
</script>	
<?php } ?>
