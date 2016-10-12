<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
	<div class="page-header">
		<div class="container-fluid">
			<div class="pull-right">
				<a href="<?php echo $back; ?>" data-toggle="tooltip" title="<?php echo $button_back; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
			</div>
			<h1><?php echo $heading_title; ?></h1>
			<ul class="breadcrumb">
				<?php foreach ($breadcrumbs as $breadcrumb) { ?>
				<li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
				<?php } ?>
			</ul>
		</div>
	</div>
	<div class="container-fluid">
		<?php if ($error_warning) { ?>
		<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
			<button type="button" class="close" data-dismiss="alert">&times;</button>
		</div>
		<?php } ?>
		<?php if ($success) { ?>
		<div class="alert alert-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?>
			<button type="button" class="close" data-dismiss="alert">&times;</button>
		</div>
		<?php } ?>
		<?php if ((!$error_warning) && (!$success)) { ?>
		<div id="test_export_notification" class="alert alert-info"><i class="fa fa-info-circle"></i>
			<div id="test_export_loading"><img src="<?php echo HTTP_SERVER; ?>/view/image/export-import/loading.gif" /><?php echo $text_loading_notifications; ?></div>
			<button type="button" class="close" data-dismiss="alert">&times;</button>
		</div>
		<?php } ?>

		<div class="panel panel-default">
			<div class="panel-body">

				<ul class="nav nav-tabs">
					<li class="active"><a href="#tab-export" data-toggle="tab"><?php echo $tab_export; ?></a></li>
					<li><a href="#tab-import" data-toggle="tab"><?php echo $tab_import; ?></a></li>

				</ul>

				<div class="tab-content">

					<div class="tab-pane active" id="tab-export">
						<form action="<?php echo $export; ?>" method="post" enctype="multipart/form-data" id="export" class="form-horizontal">
							<table class="form">
								<tr>
									<td><?php echo $entry_export; ?></td>
								</tr>


								<tr>
									<td class="buttons"><a onclick="downloadData();" class="btn btn-primary"><span><?php echo $button_export; ?></span></a></td>
								</tr>
                                                              
							</table>
						</form>
					</div>

					<div class="tab-pane" id="tab-import">
						<form action="<?php echo $import; ?>" method="post" enctype="multipart/form-data" id="import" class="form-horizontal">
							<table class="form">
								<tr>
									<td>
                                                                            <span><?php echo $entry_import; ?> <br/> </span>
									</td>
								</tr>

								<tr>
									<br /><input type="file" name="upload" id="upload" /></td>
								</tr>
								<tr>
									<td class="buttons"><a onclick="uploadData();" class="btn btn-primary"><span><?php echo $button_import; ?></span></a></td>
								</tr>
							</table>
						</form>
					</div>

				</div>
			</div>
		</div>

	</div>

<script type="text/javascript">

function getNotifications() {
	$('#test_export_notification').html('<i class="fa fa-info-circle"></i><button type="button" class="close" data-dismiss="alert">&times;</button> <div id="test_export_loading"><img src="<?php echo HTTP_SERVER; ?>/view/image/export-import/loading.gif" /><?php echo $text_loading_notifications; ?></div>');
	setTimeout(
		function(){
			$.ajax({
				type: 'GET',
				url: 'index.php?route=extension/test_export/getNotifications&token=<?php echo $token; ?>',
				dataType: 'json',
				success: function(json) {
					if (json['error']) {
						$('#test_export_notification').html('<i class="fa fa-info-circle"></i><button type="button" class="close" data-dismiss="alert">&times;</button> '+json['error']+' <span style="cursor:pointer;font-weight:bold;text-decoration:underline;float:right;" onclick="getNotifications();"><?php echo $text_retry; ?></span>');
					} else if (json['message']) {
						$('#test_export_notification').html('<i class="fa fa-info-circle"></i><button type="button" class="close" data-dismiss="alert">&times;</button> '+json['message']);
					} else {
						$('#test_export_notification').html('<i class="fa fa-info-circle"></i><button type="button" class="close" data-dismiss="alert">&times;</button> '+'<?php echo $error_no_news; ?>');
					}
				},
				failure: function(){
					$('#test_export_notification').html('<i class="fa fa-info-circle"></i><button type="button" class="close" data-dismiss="alert">&times;</button> '+'<?php echo $error_notifications; ?> <span style="cursor:pointer;font-weight:bold;text-decoration:underline;float:right;" onclick="getNotifications();"><?php echo $text_retry; ?></span>');
				},
				error: function() {
					$('#test_export_notification').html('<i class="fa fa-info-circle"></i><button type="button" class="close" data-dismiss="alert">&times;</button> '+'<?php echo $error_notifications; ?> <span style="cursor:pointer;font-weight:bold;text-decoration:underline;float:right;" onclick="getNotifications();"><?php echo $text_retry; ?></span>');
				}
			});
		},
		500
	);
}

function check_range_type(export_type) {
	if ((export_type=='p') || (export_type=='c')) {
		$('#range_type').show();
		$('#range_type_id').prop('checked',true);
		$('#range_type_page').prop('checked',false);
		$('.id').show();
		$('.page').hide();
	} else {
		$('#range_type').hide();
	}
}

$(document).ready(function() {

	check_range_type($('input[name=export_type]:checked').val());

	$("#range_type_id").click(function() {
		$(".page").hide();
		$(".id").show();
	});

	$("#range_type_page").click(function() {
		$(".id").hide();
		$(".page").show();
	});

	$('input[name=export_type]').click(function() {
		check_range_type($(this).val());
	});

	$('span.close').click(function() {
		$(this).parent().remove();
	});

	$('a[data-toggle="tab"]').click(function() {
		$('#test_export_notification').remove();
	});

	getNotifications();
});

function checkFileSize(id) {
	// See also http://stackoverflow.com/questions/3717793/javascript-file-upload-size-validation for details
	var input, file, file_size;

	if (!window.FileReader) {
		// The file API isn't yet supported on user's browser
		return true;
	}

	input = document.getElementById(id);
	if (!input) {
		// couldn't find the file input element
		return true;
	}
	else if (!input.files) {
		// browser doesn't seem to support the `files` property of file inputs
		return true;
	}
	else if (!input.files[0]) {
		// no file has been selected for the upload
		alert( "<?php echo $error_select_file; ?>" );
		return false;
	}
	else {
		file = input.files[0];
		file_size = file.size;
		<?php if (!empty($post_max_size)) { ?>
		// check against PHP's post_max_size
		post_max_size = <?php echo $post_max_size; ?>;
		if (file_size > post_max_size) {
			alert( "<?php echo $error_post_max_size; ?>" );
			return false;
		}
		<?php } ?>
		<?php if (!empty($upload_max_filesize)) { ?>
		// check against PHP's upload_max_filesize
		upload_max_filesize = <?php echo $upload_max_filesize; ?>;
		if (file_size > upload_max_filesize) {
			alert( "<?php echo $error_upload_max_filesize; ?>" );
			return false;
		}
		<?php } ?>
		return true;
	}
}

function uploadData() {
	if (checkFileSize('upload')) {
		$('#import').submit();
	}
}

function isNumber(txt){ 
	var regExp=/^[\d]{1,}$/;
	return regExp.test(txt); 
}

function downloadData() {

		$('#export').submit();

}


</script>

</div>
<?php echo $footer; ?>
