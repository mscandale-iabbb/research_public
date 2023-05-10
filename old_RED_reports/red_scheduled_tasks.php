<?php

/*
 * 11/09/16 MJS - rewrote file
 * 12/08/16 MJS - fixed datefrom and dateto
 * 05/08/17 MJS - added option to Run All
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

	<p class='page_title'>Scheduled Tasks</p>

	<p>
	<input type=checkbox ng-model="iTesting" />Testing
	&nbsp; &nbsp; &nbsp; &nbsp;
	<input type=text ng-model="lastmonth" style="width:5%" />
		/  <input type=text ng-model="lastmonthyear" style="width:5%" />
	<!--
	&nbsp; &nbsp; &nbsp; &nbsp;
	<input ng-show="! runningall" type=button class='submit_button' value='    Run All    ' ng-click='RunAll()' \>
	<i>(This takes many minutes to run.)</i>
	-->
	</p>

	<table class='report_table'>
	<tr ng-repeat="task in tasks">

	<td class='table_cell'>
	{{task.oUser}}

	<td class='table_cell'>
	{{task.oTitle}}

	<td class='table_cell'>
	<input ng-disabled="running" type=submit value="  Run  " ng-click="Run(task)" />

	</table>

	</span>

	</div>
	</div>

	<!------------------------------------------------------------------------>

	<script>
	var app = angular.module('App1', []);

	app.controller('xcontroller', function(\$scope, \$http) {

		\$scope.RunAll = function() {
			\$scope.runningall = true;
			for (var k in \$scope.tasks) {
				/*wait(3000);*/
				\$scope.Run(\$scope.tasks[k]);
			}
		};

		\$scope.Run = function(this_task) {
			\$scope.running = true;

			var datefrom = \$scope.lastmonth + '/1/' + \$scope.lastmonthyear;
			var rawdateto = new Date(\$scope.lastmonthyear, \$scope.lastmonth - 1 + 1, 1); /* (JavasScript months are 0-11) */
			rawdateto.setDate(rawdateto.getDate() - 1);
			var dateto = (rawdateto.getMonth() + 1) + '/' + rawdateto.getDate() + '/' + rawdateto.getFullYear(); /* (JavasScript months are 0-11) */

			this_task.iBBBID = this_task.oBBBID;
			this_task.iPageNumber = 1;
			this_task.iPageSize = 500;
			this_task.iMaxRecs = 500;
			this_task.iMonthFrom = \$scope.lastmonth;
			this_task.iYearFrom = \$scope.lastmonthyear;
			this_task.iMonthTo = \$scope.lastmonth;
			this_task.iYearTo = \$scope.lastmonthyear;
			this_task.iDateFrom = datefrom;
			this_task.iDateTo = dateto;
			this_task.output_type = 'excel';
			\$http({
				method: 'POST',
				url: this_task.oReportName,
				data: this_task,
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
					'enctype': 'multipart/form-data'
				}
			})
			.success(function(data) {
				/*alert(data);*/
				var iSubject = 'RED ' + this_task.oTitle + ' report for ' +
					this_task.oBBB + ' for ' + this_task.iMonthFrom + '/' +
					this_task.iYearFrom;
				\$scope.SendEmail(data, this_task.oUser, iSubject, this_task.oReportName, \$scope.iTesting);
			});
		};

		\$scope.SendEmail = function(iMessage, iRecipient, iSubject, iReportName, iTesting) {
			\$http({
				method: 'POST',
				url: 'red_scheduled_tasks-db.php',
				data: {
					iMessage: iMessage,
					iRecipient: iRecipient,
					iSubject: iSubject,
					iReportName: iReportName,
					iTesting: iTesting
				},
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
					'enctype': 'multipart/form-data'
				}
			})
			.success(function(data) {
				/*alert(data.message);*/
				\$scope.running = false;
			});
		};

		\$scope.GetTasks = function() {
			// get tasks
			\$http.get('red_scheduled_tasks-db.php', {params: {iType: 'tasks'}}).then(
				function(response) {
					\$scope.tasks = response.data;
				}
			);
		};

		var thismonth = new Date().getMonth() + 1;
		var thisyear = new Date().getFullYear();
		\$scope.lastmonth = thismonth - 1;
		\$scope.lastmonthyear = thisyear;
		if (\$scope.lastmonth == 0) {
			\$scope.lastmonth = 12;
			\$scope.lastmonthyear--;
		}

		\$scope.runningall = false;
		\$scope.running = false;
		\$scope.iTesting = false;

		\$scope.GetTasks();

	});

	function wait(ms){
		var start = new Date().getTime();
		var end = start;
		while (end < start + ms) {
			end = new Date().getTime();
		}
	}

	</script>

EOT;

$page->AddHTML($html);
$page->SlideAll();

?>