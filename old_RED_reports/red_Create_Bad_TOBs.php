<?php

/*
 * 07/17/17 MJS - new file
 */

include '../intranet/init_standard.php';

include 'headerlinks.php';
$page = new page($SITE_TITLE, '', $SITE_TITLE, $links);
$page->AddHeader();
$page->AddTabStrip($tabs);

$page->CheckCouncilOnly($BBBID);


$html = <<< EOT
	<script src="{$SETTINGS['ANGULAR_CDN']}"></script>

	<div class='main_section'>
	<div class='inner_section'>

	<span ng-app=App1 ng-controller=xcontroller>

	<p class='page_title'>Create Bad TOB Records for BBBs</p>


	<table class='report_table'>
	<tr ng-repeat="bbb in bbbs">

	<td class='table_cell'>
	{{bbb.oBBB}}

	<td class='table_cell'>
	{{bbb.oCount}}

	<td class='table_cell'>
	<input ng-disabled="running" type=submit value="  Run  " ng-click="Run(bbb)" />

	</table>

	</span>

	</div>
	</div>

	<!------------------------------------------------------------------------>

	<script>
	var app = angular.module('App1', []);

	app.controller('xcontroller', function(\$scope, \$http) {


		\$scope.Run = function(this_bbb) {
			\$scope.running = true;

			this_bbb.iBBBID = this_bbb.oBBBID;
			\$http({
				method: 'POST',
				url: "red_Create_Bad_TOBs-db.php",
				data: this_bbb,
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
					'enctype': 'multipart/form-data'
				}
			})
			.success(function(data) {
				/*alert(data);*/
				\$scope.GetBBBs();
				\$scope.running = false;
			});
		};

		\$scope.GetBBBs = function() {
			\$http.get('red_Create_Bad_TOBs-db.php', {params: {iType: 'bbbs'}}).then(
				function(response) {
					\$scope.bbbs = response.data;
				}
			);
		};

		\$scope.running = false;

		\$scope.GetBBBs();

	});

	</script>

EOT;

$page->AddHTML($html);
$page->SlideAll();

?>