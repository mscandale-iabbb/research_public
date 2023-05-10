<?php

/*
 * 07/12/18 MJS - new file
 * 08/02/18 MJS - no longer needed
 */


return;



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

	<p class='page_title'>Customer Reviews</p>

	<table class='report_table'>
	<tr ng-repeat="task in tasks">

	<td class='table_cell'>
	Count: {{task.oCount}}

	<td class='table_cell'>
	Update how many: <input ng-model=iCount ng-disabled="running" type=text />

	<td class='table_cell'>
	<input ng-disabled="running" type=submit value="  Run  " ng-click="Run(iCount)" />

	</table>

	</span>

	</div>
	</div>

	<!------------------------------------------------------------------------>

	<script>
	var app = angular.module('App1', []);

	app.controller('xcontroller', function(\$scope, \$http) {

		\$scope.Run = function(iCount) {
			\$scope.running = true;
			/*alert(iCount);*/
			\$http.get('red_update_reviews-db.php', {params: {iType: 'run', iCount: iCount}}).then(
				function(response) {
					\$scope.running = false;
					alert(response.data);
				}
			);
		};

		\$scope.GetTasks = function() {
			// get tasks
			\$http.get('red_update_reviews-db.php', {params: {iType: 'tasks'}}).then(
				function(response) {
					\$scope.tasks = response.data;
				}
			);
		};

		\$scope.running = false;
		\$scope.iCount = 1000;

		\$scope.GetTasks();

	});

	</script>

EOT;

$page->AddHTML($html);
$page->SlideAll();

?>