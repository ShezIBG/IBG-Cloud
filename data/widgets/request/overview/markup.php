<div class="row">
	<div class="col-md-1 col-lg-2 hidden-sm"></div>
	<div class="col-md-10 col-lg-8 col-sm-12">
		<div class="jarviswidget  grid-stack-item-content page-widget jarviswidget-color-purple">
			<header>
				<span class="widget-icon">
					<i class="eticon myWidget-colorIcon fa fa-pencil-square-o fa-2x eticon-shadow"></i>
				</span>
				<p class="myWidget-title">Request</p>
			</header>
			<div>
				<div class="jarviswidget-editbox"></div>
				<div class="widget-body">
					<div class="report-wizard wizard" id="fuelux-wizard" data-target="#step-container">
						<ul class="wizard-steps steps">
							<li data-target="#step1" class="active" style="min-width: 100%; max-width: 100%;">
								<span class="step myStepSpace description">Request a feature</span>
							</li>
						</ul>
					</div>
					<!-- <span class="myWidget-title-inside">Kindly fill the form below</span> -->
					<div class="step-content" id="step-container">
						<form id="report-wizard" action="" class="form-horizontal report-wizard">
							<div class="step-pane active" id="step1">
								<fieldset>
									<div class="form-group">
										<label class="col-md-3 control-label">Name<span class="txt-color-red">*</span></label>
										<div class="col-md-9">
											<input name="report_building" id="report_building_select" placeholder="<?= $user->info->name; ?>" class="form-control" disabled required>
											<i></i>
										</div>
									</div>
									<div class="form-group">
										<label class="col-md-3 control-label">Email<span class="txt-color-red">*</span></label>
										<div class="col-md-9">
											<input placeholder="<?= $user->info->email_addr; ?>" name="report_building" id="report_building_select" class="form-control" disabled required>
											<i></i>
										</div>
									</div>
									<!-- <div class="form-group">
										<label class="col-md-3 control-label">Severity<span class="txt-color-red">*</span></label>
										<div class="col-md-9">
											<Select name="report_building" id="report_building_select" class="form-control" required>
												<option value="">None</option>	
												<option value="">High</option>
												<option value="">Moderate</option>
												<option value="">Low</option>
											</select>
											<i></i>
										</div>
									</div> -->
									<div class="form-group">
										<label class="col-md-3 control-label">Enter Request<span class="txt-color-red">*</span></label>
										<div class="col-md-9">
											<textarea name="report_building" id="report_building_select" class="form-control" required></textarea>
											<i></i>
										</div>
									</div>
								</fieldset>
							</div>
						</form>
						<div>
							<div class="wizard-actions">
								<button class="btn btn-lg btn-prev bg-color-blue txt-color-white">
									Send
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>