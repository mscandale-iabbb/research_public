<?php

/*
 * 07/17/17 MJS - new file
 */

include '../intranet/init_server.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// get POST from AngularJS for all POST calls
	$_POST = json_decode(file_get_contents('php://input'), true);

	$result = CreateRecords();
}
else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	$iType = NoApost($_GET['iType']);
	if ($iType == 'bbbs') {
		$result = RetrieveBBBs();
	}
}
echo $result;

function CreateRecords() {
	global $conn;
	$iBBBID = Numeric2($_POST['iBBBID']);

	$insert = "
		INSERT INTO BadBusinessTOBID
			(BBBID, BusinessID, TOBID, DateCreated, ReasonDescription)
		SELECT DISTINCT
			'{$iBBBID}',
			b.BusinessID,
			b.TOBID,
			GETDATE(),
			case
				when b.TOBID = '10019-000' and b.BusinessName like '%carpet%' and b.BusinessName not like '%carpetn%' and b.BusinessName not like '%carpeter%' then 'Carpet care is not carpentry'
				when b.TOBID = '50122-000' and t2.TOBID is null and b.BusinessName not like '%suppl%' and b.BusinessName not like '%decorat%' and b.BusinessName not like '%wares%' and b.Website not like '%suppl%' then 'Bakeries are not necessarily bakery supply stores'
				when b.TOBID = '60288-000' and t2.TOBID is null and b.BusinessName not like '%association%' and b.BusinessName not like '%network%' and b.BusinessName not like '%finder%' and b.BusinessName not like '%board%' and b.BusinessName not like '%list%' and b.BusinessName not like '%dentists%' and b.BusinessName not like '%society%' and b.BusinessName not like '%directory%' then 'Dentists are not dentist information bureaus'
				when b.TOBID = '70562-100' and t2.TOBID is null and b.BusinessName not like '%driver%' and b.BusinessName not like '% car service%' and b.BusinessName not like '%share%' and b.BusinessName not like '%shuttle%' and b.BusinessName not like '%transport%' and b.Website not like '%carservice%' then 'Taxis, limos, and auto repair shops are not necessarily car services'
				when b.TOBID = '10012-000' and t2.TOBID is null and b.BusinessName not like '%consult%' and b.Website not like '%consult%' then 'Builders are not necessarily building consultants'
				when b.TOBID = '00108-000' and t2.TOBID is null and b.BusinessName not like '%semin%' and b.BusinessName not like '%genetic%' and b.BusinessName not like '%reproduc%' and b.BusinessName not like '%laborator%' and b.Website not like '%genetic%' then 'Breeders are not necessarily artificial insemination laboratories'
				when b.TOBID = '30024-000' and (b.BusinessName like '%sales%' or b.BusinessName like '%insurance%' or b.BusinessName like '%collision%' or b.BusinessName like '%repair%' or b.BusinessName like '%towing%') then 'Car manufacturers are not necessarily car dealers or car service shops'
				when b.TOBID = '60478-000' and t2.TOBID is null and (' ' + b.BusinessName like '% house%' or ' ' + b.BusinessName like '% home%' or b.BusinessName like '%maid%' or b.BusinessName like '%chimney%' or b.BusinessName like '%power wash%' or b.BusinessName like '%pressure wash%' or b.BusinessName like '%pressure clean%') and b.BusinessName not like '%janitor%' and b.BusinessName not like '%office%' and b.BusinessName not like '%commercial%' and b.BusinessName not like '%building%' and b.BusinessName not like '%warehouse%' then 'Housekeepers, pressure washers, etc. are not necessarily janitors'
				when b.TOBID = '30024-000' and NOT (b.BusinessName like '%america%' or b.BusinessName like '%canad%' or b.BusinessName like '%audi%' or b.BusinessName like '%division%' or b.BusinessName like '%chrysler%' or b.BusinessName like '%assembl%' or b.BusinessName like '%plant%' or b.BusinessName like '%worldwide%' or b.BusinessName like 'ford%' or b.BusinessName like '%region%' or b.BusinessName like 'g m c%' or b.BusinessName like 'gmc%' or b.BusinessName like 'general motors%' or b.BusinessName like 'honda %' or b.BusinessName like 'hyundai%' or b.BusinessName like '%isuzu%' or b.BusinessName like 'kia%' or b.BusinessName like 'lexus%' or b.BusinessName like 'mazda%' or b.BusinessName like 'mercedes%' or b.BusinessName like 'mercury%' or b.BusinessName like 'nissan%' or b.BusinessName like '%usa%' or b.BusinessName like 'toyota %' or b.BusinessName like '%volkswag%' or b.BusinessName like '%bmw%' or b.BusinessName like 'chevy %' or b.BusinessName like 'dodge%' or b.BusinessName like 'fisker%' or b.BusinessName like 'global electric%') then 'Auto dealers, insurers, servicers, and parts makers are rarely auto makers'
				when b.TOBID = '00107-000' and t2.TOBID is null and b.BusinessName not like '%clinic%' and b.BusinessName not like '%hosp%' and b.BusinessName not like '%medical%' and b.BusinessName not like '%care%' and b.BusinessName not like '%kare%' and b.BusinessName not like '%imaging%' and b.BusinessName not like '%vaccin%' and b.BusinessName not like '%rehab%' and b.BusinessName not like '%medic%' and b.BusinessName not like '% eye %' and b.BusinessName not like '% ER,%' and b.BusinessName not like '%thyroid%' and b.BusinessName not like '%healing%' and b.BusinessName not like '%holistic%' and b.BusinessName not like '%911%' and b.BusinessName not like '%allerg%' and b.BusinessName not like '%wellness%' and b.BusinessName not like '%health%' and b.BusinessName not like '%emergency%' then 'Kennels, breeders, and vets are not necessarily animal hospitals'
				when b.TOBID = '50018-000' and t2.TOBID is null and (b.BusinessName like '%repair%' or b.BusinessName like '%service%' or b.BusinessName like '%refrigerat%' or b.BusinessName like '%parts%' or b.BusinessName like '%washer%') and b.BusinessName not like '%sales%' then 'Appliance repair shops are not necessarily appliance dealers'
				when b.TOBID = '50297-000' and t2.TOBID is null then 'Waste collection services are not household garbage disposal device makers/sellers'
				when b.TOBID = '30477-000' and t2.TOBID is null and not ((b.BusinessName like '%food%' or b.BusinessName like '%suppl%' or b.BusinessName like '%feed%' or b.BusinessName like '%product%' or b.BusinessName like '%bake%' or b.BusinessName like '%biscuit%' or b.BusinessName like '%bone%' or b.BusinessName like '%gourmet%' or b.BusinessName like '%mill%' or b.BusinessName like '%nutri%' or b.BusinessName like '%treat%' or b.BusinessName like 'healthy%' or b.BusinessName like '%farm%' or b.BusinessName like '%blend%' or b.BusinessName like '%natural%' or b.BusinessName like 'nature%' or b.BusinessName like '%organic%' or b.BusinessName like '%chew%' or b.BusinessName like '%grain%') and b.BusinessName not like '%groom%') then 'Pet shops and groomers are not necessarily pet food makers'
				when b.TOBID = '00159-000' and t2.TOBID is null and not ((b.BusinessName like '%cattle%' or b.BusinessName like '%livestock%' or b.BusinessName like '%ranch%' or b.BusinessName like '%breed%' or b.BusinessName like '%genetic%' or b.BusinessName like '%alpaca%' or b.BusinessName like '%horse%' or b.BusinessName like '%pig%' or b.BusinessName like '%hog%' or b.BusinessName like '%cow%' or b.BusinessName like '%goat%' or b.BusinessName like '%hereford%' or b.BusinessName like '%bulls%' or b.BusinessName like '%equine%' or b.BusinessName like '%equestri%' or b.BusinessName like '%farm%' or b.BusinessName like '%sire%' or b.BusinessName like '%chicken%' or b.BusinessName like '%ostrich%' or b.BusinessName like '%poultry%' or b.BusinessName like '%pork%' or b.BusinessName like '%semen%' or b.BusinessName like '%reproduc%' or b.BusinessName like '%hatch%') and b.BusinessName not like '%pet%') then 'Pet breeders are not necessarily livestock breeders'
				when b.TOBID = '50016-000' and t2.TOBID is null and (b.BusinessName like '%repair%' or b.BusinessName like '%service%' or b.BusinessName like '%svc%') and b.BusinessName not like '%sales%' then 'Appliance repair shops are not necessarily appliance dealers'
				when b.TOBID = '50017-000' and t2.TOBID is null and not (b.BusinessName like '%parts%' or b.BusinessName like '%suppl%' or b.BusinessName like '%equipment%') then 'Appliance sales and repair shops are not necessarily appliance parts sellers' 
				when b.TOBID = '10074-000' and t2.TOBID is null and not ( b.BusinessName like '%faux%' or b.BusinessName like '%finish%' or b.BusinessName like '%decorat%' or b.BusinessName like '%design%' or b.BusinessName like '%specialty%' or b.BusinessName like '%artistic%' or b.BusinessName like 'curb %' or b.BusinessName like '%adornment%' or b.BusinessName like '%mural%' or b.BusinessName like '%custom%' or b.BusinessName like '%airbrush%' ) then 'General painters and others are not necessarily decorative painters'
				when b.TOBID = '60134-000' and t2.TOBID is null and b.BusinessName like '%spa%' and b.BusinessName not like '%salon%' and b.BusinessName not like '%nail%' and b.BusinessName not like '%hair%' and b.BusinessName not like '%studio%' and b.BusinessName not like '%cut%' then 'Day spas are not necessarily beauty salons'
				when b.TOBID = '50602-000' and t2.TOBID is null and (b.BusinessName like '%repair%' or b.BusinessName like '%service%' or b.BusinessName like '%day spa%') and b.BusinessName not like '%sales%' then 'Hot tub repair shops are not necessarily hot tub dealerships'
				when (b.TOBID = '10177-000' or b.TOBID = '10035-000' or b.TOBID = '10079-000') and t2.TOBID is null and (b.BusinessName like '%carpenter%' or b.BusinessName like '%carpentry%' or b.BusinessName like '%home build%' or b.BusinessName like '%roof%' or b.BusinessName like '%siding%' or b.BusinessName like '%insulat%' or b.BusinessName like '%handym%' or b.BusinessName like '%paint%' or b.BusinessName like '%window%' or b.BusinessName like '%electric%' or b.BusinessName like '%plumb%' or b.BusinessName like '%waterproof%' or b.BusinessName like '%gutter%' or b.BusinessName like '%concrete%' or b.BusinessName like '%deck%' or b.BusinessName like '%patio%' or b.BusinessName like '%tile%' or b.BusinessName like '%brick%' or b.BusinessName like '%paving%' or b.BusinessName like '%chimney%' or b.BusinessName like '%pressure wash%' or b.BusinessName like '%power wash%' or b.BusinessName like '%pressure clean%' or b.BusinessName like '%kitchen%' or b.BusinessName like '%bathroom%' or b.BusinessName like '%solar%' or b.BusinessName like '%landscap%' or b.BusinessName like '%cabinet%' or b.BusinessName like '%masonry%' or b.BusinessName like '% tree %' or b.BusinessName like '%heating%' or b.BusinessName like '%homes%' or b.BusinessName like '%pools%' or b.BusinessName like '%garage%' or b.BusinessName like '%door%' or b.BusinessName like '%porch%' or b.BusinessName like '%sealing%' or b.BusinessName like '%coating%' or b.BusinessName like '%staining%' or b.BusinessName like '%floor%' or b.BusinessName like '%stone%' or b.BusinessName like '%framing%' or b.BusinessName like '%air conditioning%' or b.BusinessName like '%counter%' or b.BusinessName like '%shelters%' or b.BusinessName like '%mirror%' or b.BusinessName like '%sandblast%' or b.BusinessName like '%marble%' or b.BusinessName like '%inspection%' or b.BusinessName like '% log %' or b.BusinessName like '%excavat%' or b.BusinessName like '%drywall%' or b.BusinessName like '%hardwood%' or b.BusinessName like '%refrigerat%' or b.BusinessName like '%shutter%' or b.BusinessName like '% frame %' or b.BusinessName like '%woodwork%' or b.BusinessName like '%granite%' or b.BusinessName like '%aluminum%' or b.BusinessName like '%stucco%' or b.BusinessName like '%welding%' or b.BusinessName like '%basement%' or b.BusinessName like '%fence%' or b.BusinessName like '%exterminat%' or b.BusinessName like '%cleaning%' or b.BusinessName like '%foundation%' or b.BusinessName like '%fire protection%' or b.BusinessName like '% duct%' or b.BusinessName like '%engineer%' or b.BusinessName like '%abatement%' or b.BusinessName like '% pool %' or b.BusinessName like '%fumigat%' or b.BusinessName like '%carport%' or b.BusinessName like '%awning%' or b.BusinessName like '%cooling%' or b.BusinessName like '%marine%' or b.BusinessName like '%millwork%' or b.BusinessName like '%plaster%' or b.BusinessName like '%fencing%' or b.BusinessName like '%septic%' or b.BusinessName like '%custom build%' or b.BusinessName like '%handy m%' or b.BusinessName like '%termite%') then 'Specialized contractors are not necessarily general home improvement contractors'
				when (b.TOBID = '60971-000' or b.TOBID = '60429-200') and t2.TOBID is null and (b.BusinessName like '%surge%' or b.BusinessName like '%dental%' or b.BusinessName like '%dentist%' or b.BusinessName like '%chiro%' or b.BusinessName like '%acupunct%' or b.BusinessName like '%holsitic%' or b.BusinessName like '%gastro%' or b.BusinessName like '%endocrin%' or b.BusinessName like '%ophtha%' or b.BusinessName like '%anesth%' or b.BusinessName like '%psych%' or b.BusinessName like '%urolog' or b.BusinessName like '%oncol%' or b.BusinessName like '%osteo%' or b.BusinessName like '%radiol%' or b.BusinessName like '%cardiol%' or b.BusinessName like '%gyn%' or b.BusinessName like '%dermat%' or b.BusinessName like '%internal med%' or b.BusinessName like '%ear, nose%' or b.BusinessName like '%ear nose%' or b.BusinessName like '%ortho%' or b.BusinessName like '%pedia%' or b.BusinessName like '%obste%' or b.BusinessName like '%pulmon%' or b.BusinessName like '%hormon%' or b.BusinessName like '%sports%' or b.BusinessName like '%nephro%' or b.BusinessName like '%maxill%' or b.BusinessName like '%imaging%' or b.BusinessName like '%diagnos%' or b.BusinessName like '%physical therap%' or b.BusinessName like '%hypno%' or b.BusinessName like '%myo%' or b.BusinessName like '%cryo%' or b.BusinessName like '%biofeedback%' or b.BusinessName like '%colon%' or b.BusinessName like '%massage%' or b.BusinessName like '%hydro%' or b.BusinessName like '%occupational%' or b.BusinessName like '%laser%' or b.BusinessName like '%aroma%' or b.BusinessName like '%oxygen%' or b.BusinessName like '%autis%' or b.BusinessName like '%reiki%' or b.BusinessName like '%equipment%' or b.BusinessName like '%supplies%' or b.BusinessName like '%supply%' or b.BusinessName like '%nutrition%' or b.BusinessName like '%speech%' or b.BusinessName like '%respir%' or b.BusinessName like '%fitness%' or b.BusinessName like '%emergency%' or b.BusinessName like '%clinic,%' or b.BusinessName like '%clinic' or b.BusinessName like '%clinics' or b.BusinessName like '%clinic %' or b.BusinessName like '%rehab%' or b.BusinessName like '%rheum%' or b.BusinessName like '%arthrit%' or b.BusinessName like '%allerg%' or b.BusinessName like '%asthma%' or b.BusinessName like '%testing%' or b.BusinessName like '% ent %' or b.BusinessName like '% ent' or b.BusinessName like 'ent %' or b.BusinessName like '%laborator%' or b.BusinessName like '%labs%' or b.BusinessName like '%lab %' or b.BusinessName like '%lab' or b.BusinessName like '%optical%' or b.BusinessName like '%instrument%' or b.BusinessName like '%research%' or b.BusinessName like '%cardiac%' or b.BusinessName like '%vasc%' or b.BusinessName like '%birth%' or b.BusinessName like '%midwife%' or b.BusinessName like '%midwive%' or b.BusinessName like '%spine%' or b.BusinessName like '%spinal%' or b.BusinessName like '%athlet%' or b.BusinessName like '%pain%' or b.BusinessName like '%hospital%' or b.BusinessName like '%ambul%' or b.BusinessName like '%hospice%' or b.BusinessName like '%home%' or b.BusinessName like '%nurs%' or b.BusinessName like '%blood%' or b.BusinessName like '%cancer%' or b.BusinessName like '%phleb%' or b.BusinessName like '%family%' or b.BusinessName like '%ultrasound%' or b.BusinessName like '%natal%' or b.BusinessName like '%diabet%' or b.BusinessName like '%preg%' or b.BusinessName like '%cosmetic%' or b.BusinessName like '%urgent%' or b.BusinessName like '%staff%' or b.BusinessName like '%consult%' or b.BusinessName like '%personnel%' or b.BusinessName like '%mri%' or b.BusinessName like '% scan%' or b.BusinessName like '%joint%' or b.BusinessName like '%foot%' or b.BusinessName like '%child%' or b.BusinessName like '%breast%' or b.BusinessName like '%neck%' or b.BusinessName like '%laryn%' or b.BusinessName like '%scooter%' or b.BusinessName like '%wheelchair%' or b.BusinessName like '%diet%' or b.BusinessName like '%dialy%' or b.BusinessName like '%electrol%' or b.BusinessName like '%lasik%' or b.BusinessName like '% vision%' or b.BusinessName like 'vision %' or b.BusinessName like '%weight%' or b.BusinessName like '% eye%' or b.BusinessName like 'eye%' or b.BusinessName like '%lung%' or b.BusinessName like '%sleep %' or b.BusinessName like '%sleep' or b.BusinessName like '%counsel%' or b.BusinessName like '% mental%' or b.BusinessName like 'mental%' or b.BusinessName like '%immediate%' or b.BusinessName like '%herbal%' or b.BusinessName like '%herbs%' or b.BusinessName like '%insurance%' or b.BusinessName like '%billing%' or b.BusinessName like '%data%' or b.BusinessName like '%yoga%' or b.BusinessName like '%urology%' or b.BusinessName like '%pharma%' or b.BusinessName like '%dds%' or b.BusinessName like '%d.d.s.%' or b.BusinessName like '%dmd%' or b.BusinessName like '%d.m.d.%' or b.BusinessName like '%assisted%' or b.BusinessName like '%cpr%' or b.BusinessName like '%visit%' or b.BusinessName like '%osteo%' or b.BusinessName like '%audiol%') then 'Specialized medical services are not necessarily general medical services'
				when b.TOBID = '50308-000' and t2.TOBID is null and (b.BusinessName like '%jewel%' or b.BusinessName like '%dollar%' or b.BusinessName like '%discount%' or b.BusinessName like '%fashion%' or b.BusinessName like '%cloth%' or b.BusinessName like '%hardware%' or b.BusinessName like '%candy%' or b.BusinessName like '%sport%' or b.BusinessName like '%furniture%' or b.BusinessName like '%conveni%' or b.BusinessName like '%grocer%' or b.BusinessName like '%gift%' or b.BusinessName like '%antique%' or b.BusinessName like '%book%' or b.BusinessName like '%sunglass%' or b.BusinessName like '% art%' or b.BusinessName like '% hat%' or b.BusinessName like '%coat%' or b.BusinessName like '%shoe%' or b.BusinessName like '%tool%' or b.BusinessName like '%hobb%' or b.BusinessName like '%gun%' or b.BusinessName like '%collecti%' or b.BusinessName like '%collecta%' or b.BusinessName like '%coin%' or b.BusinessName like '%music%' or b.BusinessName like '%piano%' or b.BusinessName like '%guitar%' or b.BusinessName like '%pet%' or b.BusinessName like '%comic%' or b.BusinessName like '%card%' or b.BusinessName like '%nutri%' or b.BusinessName like '%fitness%' or b.BusinessName like '%surf%' or b.BusinessName like '%news%' or b.BusinessName like '%pharm%' or b.BusinessName like '%smoke%' or b.BusinessName like '%skate%' or b.BusinessName like '%patio%' or b.BusinessName like '%swim%' or b.BusinessName like '%soccer%' or b.BusinessName like '%tux%' or b.BusinessName like '%bridal%' or b.BusinessName like '%tire%' or b.BusinessName like '%game%' or b.BusinessName like '%bike%' or b.BusinessName like '%bicycl%' or b.BusinessName like '%cycle%' or b.BusinessName like '%electron%' or b.BusinessName like '%dance%' or b.BusinessName like '%food%' or b.BusinessName like '%golf%' or b.BusinessName like '%tennis%' or b.BusinessName like '%99 cent%' or b.BusinessName like '%wine%' or b.BusinessName like '%liquor%' or b.BusinessName like '%radio%' or b.BusinessName like '%phone%' or b.BusinessName like '%purse%' or b.BusinessName like '%party%' or b.BusinessName like '%decora%' or b.BusinessName like '%drug%' or b.BusinessName like '%luggage%' or b.BusinessName like '%baggage%' or b.BusinessName like '%pawn%' or b.BusinessName like '%troph%' or b.BusinessName like '%wear%' or b.BusinessName like '%adult%' or b.BusinessName like '%firew%' or b.BusinessName like '%lawn%' or b.BusinessName like '%department%' or b.BusinessName like '%beauty supp%' or b.BusinessName like '%cosmet%' or b.BusinessName like '%fragran%' or b.BusinessName like '%perfum%' or b.BusinessName like '%fram%' or b.BusinessName like '%picture%' or b.BusinessName like '%photo%' or b.BusinessName like '%watch%' or b.BusinessName like '%clock%' or b.BusinessName like '%candle%' or b.BusinessName like '%knife%' or b.BusinessName like '%knive%' or b.BusinessName like '%cutlery%' or b.BusinessName like '%coffee%' or b.BusinessName like '%auction%' or b.BusinessName like '%shirt%' or b.BusinessName like '%dress%' or b.BusinessName like '%audiol%' or b.BusinessName like '%outdoor%' or b.BusinessName like '%gourmet%' or b.BusinessName like '%bargain%' or b.BusinessName like '%boot%' or b.BusinessName like '% doll %' or b.BusinessName like '%pottery%' or b.BusinessName like '%cellular%' or b.BusinessName like '%cake%' or b.BusinessName like '%military%') then 'Specialized retail shops are not necessarily general retail shops'
				when (b.TOBID = '60094-000' or b.TOBID like '60103%' or b.TOBID like '60107%' or b.TOBID = '60035-000' or b.TOBID = '60104-000' or b.TOBID = '60990-000' or b.TOBID = '60997-000' or b.TOBID = '60158-000' or b.TOBID = '60594-000' or b.TOBID = '60951-000') and t2.TOBID is null and (b.BusinessName like '%sales%' or b.BusinessName like '%dealer%' or b.BusinessName like '%lube%' or b.BusinessName like '% tow %' or b.BusinessName like '%towing%' or b.BusinessName like '%lubri%' or b.BusinessName like '%insurance%' or b.BusinessName like '%tire%' or b.BusinessName like '%wash%' or b.BusinessName like '%parts%' or b.BusinessName like '%inspection%' or b.BusinessName like '%testing%') and b.BusinessName not like '%repair%' and b.BusinessName not like '%collision%' and b.BusinessName not like '%body%' and b.BusinessName not like '%garage%' and b.BusinessName not like '%service%' and b.BusinessName not like '%svc%' and b.BusinessName not like '%brake%' and b.BusinessName not like '%muffler%' and b.BusinessName not like '%tune%' and b.BusinessName not like '%care%' and b.BusinessName not like '%kare%' and b.BusinessName not like '%alignment%' and b.BusinessName not like '%mechanic%' then 'Specialized auto services and dealerships are not necessarily general auto services'
			end
		FROM Business b WITH (NOLOCK)
		left outer join BusinessTOBID t WITH (NOLOCK) ON
			t.BBBID = b.BBBID AND t.BusinessID = b.BusinessID
		left outer join BusinessTOBID t2 WITH (NOLOCK) ON
			t2.BBBID = b.BBBID AND t2.BusinessID = b.BusinessID and t2.TOBID != t.TOBID
		inner join BBB WITH (NOLOCK) on BBB.BBBID = b.BBBID AND BBB.BBBBranchID = '0'
		left outer join tblRatingCodes r WITH (NOLOCK) ON r.BBBRatingCode = b.BBBRatingGrade
		left outer join tblYPPA y WITH (NOLOCK) on y.yppa_code = b.TOBID
		left outer join BusinessTOBIDGood g WITH (NOLOCK) ON
			g.BBBID = t.BBBID AND g.BusinessID = t.BusinessID and g.TOBID = t.TOBID
		WHERE
			b.BBBID = '{$iBBBID}' and
			b.IsReportable = 1 and b.PublishToCIBR = 1 and
			t.PublishToCIBR = 1 and g.TOBID is null and
			(
				/* carpet companies classified as carpenters */
				(
					b.TOBID = '10019-000' and
					b.BusinessName like '%carpet%' and
					b.BusinessName not like '%carpetn%' and
					b.BusinessName not like '%carpeter%'
				) or
				/* bakeries classified as baking supply shops */
				(
					b.TOBID = '50122-000' and
					t2.TOBID is null and
					b.BusinessName not like '%suppl%' and
					b.BusinessName not like '%decorat%' and
					b.BusinessName not like '%wares%' and
					b.Website not like '%suppl%'
				) or
				/* dentists classified as dentist information bureaus */
				(
					b.TOBID = '60288-000' and
					t2.TOBID is null and
					b.BusinessName not like '%association%' and
					b.BusinessName not like '%network%' and
					b.BusinessName not like '%finder%' and
					b.BusinessName not like '%board%' and
					b.BusinessName not like '%society%' and
					b.BusinessName not like '%list%' and
					b.BusinessName not like '%dentists%' and
					b.BusinessName not like '%directory%'
				) or
				/* car repair shops classified as limo services */
				(
					b.TOBID = '70562-100' and
					t2.TOBID is null and
					b.BusinessName not like '%driver%' and
					b.BusinessName not like '% car service%' and
					b.BusinessName not like '%share%' and
					b.BusinessName not like '%shuttle%' and
					b.BusinessName not like '%transport%' and
					b.Website not like '%carservice%'
				) or
				/* builders classified as building consultants */
				(
					b.TOBID = '10012-000' and
					t2.TOBID is null and
					b.BusinessName not like '%consult%' and
					b.Website not like '%consult%'
				) or
				/* breeders and kennels classified as artificial insemination */
				(
					b.TOBID = '00108-000' and
					t2.TOBID is null and
					not (
						b.BusinessName like '%semin%' or
						b.BusinessName like '%genetic%' or
						b.BusinessName like '%reproduc%' or
						b.BusinessName like '%laborator%' or
						b.Website like '%genetic%'
					)
				) or
				/* car makers classified as car shops */
				(
					b.TOBID = '30024-000' and
					NOT (
						b.BusinessName like '%america%' or
						b.BusinessName like '%canad%' or b.BusinessName like '%audi%' or
						b.BusinessName like '%division%' or b.BusinessName like '%chrysler%' or
						b.BusinessName like '%assembl%' or b.BusinessName like '%plant%' or
						b.BusinessName like '%worldwide%' or b.BusinessName like 'ford%' or
						b.BusinessName like '%region%' or b.BusinessName like 'g m c%' or b.BusinessName like 'gmc%' or
						b.BusinessName like 'general motors%' or b.BusinessName like 'honda %' or b.BusinessName like 'hyundai%' or
						b.BusinessName like '%isuzu%' or b.BusinessName like 'kia%' or b.BusinessName like 'lexus%' or
						b.BusinessName like 'mazda%' or b.BusinessName like 'mercedes%' or b.BusinessName like 'mercury%' or
						b.BusinessName like 'nissan%' or b.BusinessName like '%usa%' or
						b.BusinessName like 'toyota %' or b.BusinessName like '%volkswag%' or b.BusinessName like '%bmw%' or
						b.BusinessName like 'chevy %' or b.BusinessName like 'dodge%' or
						b.BusinessName like 'fisker%' or b.BusinessName like 'global electric%'
					)
				) or
				/* household garbage disposals */
				(
					b.TOBID = '50297-000' and t2.TOBID is null
				) or
				/* animal hospitals */
				(
					b.TOBID = '00107-000' and t2.TOBID is null and
					b.BusinessName not like '%clinic%' and
					b.BusinessName not like '%hosp%' and
					b.BusinessName not like '%medical%' and
					b.BusinessName not like '%care%' and
					b.BusinessName not like '%kare%' and
					b.BusinessName not like '%imaging%' and
					b.BusinessName not like '%vaccin%' and
					b.BusinessName not like '%rehab%' and
					b.BusinessName not like '%medic%' and
					b.BusinessName not like '% eye %' and
					b.BusinessName not like '% ER,%' and
					b.BusinessName not like '%thyroid%' and
					b.BusinessName not like '%healing%' and
					b.BusinessName not like '%holistic%' and
					b.BusinessName not like '%911%' and
					b.BusinessName not like '%allerg%' and
					b.BusinessName not like '%wellness%' and
					b.BusinessName not like '%health%' and
					b.BusinessName not like '%emergency%'
				) or
				/* small appliance dealers */
				(
					b.TOBID = '50018-000' and t2.TOBID is null and
					(b.BusinessName like '%repair%' or b.BusinessName like '%service%' or b.BusinessName like '%refrigerat%' or b.BusinessName like '%parts%' or b.BusinessName like '%washer%') and
					b.BusinessName not like '%sales%'
				) or
				/* pet food makers */
				(
					b.TOBID = '30477-000' and t2.TOBID is null and
					not (
						(b.BusinessName like '%food%' or b.BusinessName like '%suppl%' or b.BusinessName like '%feed%' or b.BusinessName like '%product%' or b.BusinessName like '%bake%' or b.BusinessName like '%biscuit%' or b.BusinessName like '%bone%' or b.BusinessName like '%gourmet%' or b.BusinessName like '%mill%' or b.BusinessName like '%nutri%' or b.BusinessName like '%treat%' or b.BusinessName like 'healthy%' or b.BusinessName like '%farm%' or b.BusinessName like '%blend%' or b.BusinessName like '%natural%' or b.BusinessName like 'nature%' or b.BusinessName like '%organic%' or b.BusinessName like '%chew%' or b.BusinessName like '%grain%') and
						b.BusinessName not like '%groom%'
					)
				) or
				/* livestock breeders */
				(
					b.TOBID = '00159-000' and t2.TOBID is null and
					not (
						(b.BusinessName like '%cattle%' or b.BusinessName like '%livestock%' or b.BusinessName like '%ranch%' or b.BusinessName like '%breed%' or b.BusinessName like '%genetic%' or b.BusinessName like '%alpaca%' or b.BusinessName like '%horse%' or b.BusinessName like '%pig%' or b.BusinessName like '%hog%' or b.BusinessName like '%cow%' or b.BusinessName like '%goat%' or b.BusinessName like '%hereford%' or b.BusinessName like '%bulls%' or b.BusinessName like '%equine%' or b.BusinessName like '%equestri%' or b.BusinessName like '%farm%' or b.BusinessName like '%sire%' or b.BusinessName like '%chicken%' or b.BusinessName like '%ostrich%' or b.BusinessName like '%poultry%' or b.BusinessName like '%pork%' or b.BusinessName like '%semen%' or b.BusinessName like '%reproduc%' or b.BusinessName like '%hatch%') and
						b.BusinessName not like '%pet%'
					)
				) or
				/* major appliance dealers */
				(
					b.TOBID = '50016-000' and t2.TOBID is null and
					(b.BusinessName like '%repair%' or b.BusinessName like '%service%' or b.BusinessName like '%svc%') and
					b.BusinessName not like '%sales%'
				) or
				/* major appliance parts */
				(
					b.TOBID = '50017-000' and t2.TOBID is null and
					not (b.BusinessName like '%parts%' or b.BusinessName like '%suppl%' or b.BusinessName like '%equipment%')
				) or
				/* hand painting */
				(
					b.TOBID = '10074-000' and t2.TOBID is null and
					not (
						b.BusinessName like '%faux%' or b.BusinessName like '%finish%' or b.BusinessName like '%decorat%' or b.BusinessName like '%design%' or b.BusinessName like '%specialty%' or b.BusinessName like '%artistic%' or b.BusinessName like 'curb %' or b.BusinessName like '%adornment%' or b.BusinessName like '%mural%' or b.BusinessName like '%custom%' or b.BusinessName like '%airbrush%'
					)
				) or
				/* spas classified as beauty salons */
				(
					b.TOBID = '60134-000' and t2.TOBID is null and
					b.BusinessName like '%spa%' and
					b.BusinessName not like '%salon%' and b.BusinessName not like '%nail%' and
					b.BusinessName not like '%hair%' and b.BusinessName not like '%studio%' and
					b.BusinessName not like '%cut%'
				) or
				/* hot tub repairmen classified as hot tub dealers */
				(
					b.TOBID = '50602-000' and t2.TOBID is null and
					(b.BusinessName like '%repair%' or b.BusinessName like '%service%' or b.BusinessName like '%day spa%') and
					b.BusinessName not like '%sales%'
				) or
				/* specialized auto service classified as general auto service */
				(
					(
						b.TOBID = '60094-000' or b.TOBID like '60103%' or b.TOBID like '60107%' or b.TOBID = '60035-000' or
						b.TOBID = '60104-000' or b.TOBID = '60990-000' or b.TOBID = '60997-000' or b.TOBID = '60158-000' or
						b.TOBID = '60594-000' or b.TOBID = '60951-000'
					) and t2.TOBID is null and
					(
						b.BusinessName like '%sales%' or b.BusinessName like '%dealer%' or
						b.BusinessName like '%lube%' or b.BusinessName like '% tow %' or
						b.BusinessName like '%towing%' or b.BusinessName like '%lubri%' or
						b.BusinessName like '%insurance%' or b.BusinessName like '%tire%' or
						b.BusinessName like '%wash%' or b.BusinessName like '%parts%' or
						b.BusinessName like '%inspection%' or b.BusinessName like '%testing%'
					) and
					b.BusinessName not like '%repair%' and b.BusinessName not like '%collision%' and
					b.BusinessName not like '%body%' and b.BusinessName not like '%garage%' and
					b.BusinessName not like '%service%' and b.BusinessName not like '%svc%' and
					b.BusinessName not like '%brake%' and b.BusinessName not like '%muffler%' and
					b.BusinessName not like '%tune%' and b.BusinessName not like '%care%' and
					b.BusinessName not like '%kare%' and b.BusinessName not like '%alignment%' and
					b.BusinessName not like '%mechanic%'
				) or
				/* specialized retail classified as general retail */
				(
					b.TOBID = '50308-000' and t2.TOBID is null and
					(
						b.BusinessName like '%jewel%' or b.BusinessName like '%dollar%' or b.BusinessName like '%discount%' or
						b.BusinessName like '%fashion%' or b.BusinessName like '%cloth%' or b.BusinessName like '%hardware%' or
						b.BusinessName like '%candy%' or b.BusinessName like '%sport%' or b.BusinessName like '%furniture%' or
						b.BusinessName like '%conveni%' or b.BusinessName like '%grocer%' or b.BusinessName like '%gift%' or
						b.BusinessName like '%antique%' or b.BusinessName like '%book%' or b.BusinessName like '%sunglass%' or
						b.BusinessName like '% art%' or b.BusinessName like '% hat%' or b.BusinessName like '%coat%' or
						b.BusinessName like '%shoe%' or b.BusinessName like '%tool%' or b.BusinessName like '%hobb%' or
						b.BusinessName like '%gun%' or b.BusinessName like '%collecti%' or b.BusinessName like '%collecta%' or
						b.BusinessName like '%coin%' or b.BusinessName like '%music%' or b.BusinessName like '%piano%' or
						b.BusinessName like '%guitar%' or b.BusinessName like '%pet%' or b.BusinessName like '%comic%' or
						b.BusinessName like '%card%' or b.BusinessName like '%nutri%' or b.BusinessName like '%fitness%' or
						b.BusinessName like '%surf%' or b.BusinessName like '%news%' or b.BusinessName like '%pharm%' or
						b.BusinessName like '%smoke%' or b.BusinessName like '%skate%' or b.BusinessName like '%patio%' or
						b.BusinessName like '%swim%' or b.BusinessName like '%soccer%' or b.BusinessName like '%tux%' or
						b.BusinessName like '%bridal%' or b.BusinessName like '%tire%' or b.BusinessName like '%game%' or
						b.BusinessName like '%bike%' or b.BusinessName like '%bicycl%' or b.BusinessName like '%cycle%' or
						b.BusinessName like '%electron%' or b.BusinessName like '%dance%' or b.BusinessName like '%food%' or
						b.BusinessName like '%golf%' or b.BusinessName like '%tennis%' or b.BusinessName like '%99 cent%' or
						b.BusinessName like '%wine%' or b.BusinessName like '%liquor%' or b.BusinessName like '%radio%' or
						b.BusinessName like '%phone%' or b.BusinessName like '%purse%' or b.BusinessName like '%party%' or
						b.BusinessName like '%decora%' or b.BusinessName like '%drug%' or b.BusinessName like '%luggage%' or
						b.BusinessName like '%baggage%' or b.BusinessName like '%pawn%' or b.BusinessName like '%troph%' or
						b.BusinessName like '%wear%' or b.BusinessName like '%adult%' or b.BusinessName like '%firew%' or
						b.BusinessName like '%lawn%' or b.BusinessName like '%department%' or b.BusinessName like '%beauty supp%' or
						b.BusinessName like '%cosmet%' or b.BusinessName like '%fragran%' or b.BusinessName like '%perfum%' or
						b.BusinessName like '%fram%' or b.BusinessName like '%picture%' or b.BusinessName like '%photo%' or
						b.BusinessName like '%watch%' or b.BusinessName like '%clock%' or b.BusinessName like '%candle%' or
						b.BusinessName like '%knife%' or b.BusinessName like '%knive%' or b.BusinessName like '%cutlery%' or
						b.BusinessName like '%coffee%' or b.BusinessName like '%auction%' or b.BusinessName like '%shirt%' or
						b.BusinessName like '%dress%' or b.BusinessName like '%audiol%' or b.BusinessName like '%outdoor%' or
						b.BusinessName like '%gourmet%' or b.BusinessName like '%bargain%' or b.BusinessName like '%boot%' or
						b.BusinessName like '% doll %' or b.BusinessName like '%pottery%' or b.BusinessName like '%cellular%' or
						b.BusinessName like '%cake%' or b.BusinessName like '%military%'
					)
				) or
				/* specialized contractors classified as general home improvement */
				(
					(b.TOBID = '10177-000' or b.TOBID = '10035-000' or b.TOBID = '10079-000') and t2.TOBID is null and
					(
						b.BusinessName like '%carpenter%' or b.BusinessName like '%carpentry%' or b.BusinessName like '%home build%' or
						b.BusinessName like '%roof%' or b.BusinessName like '%siding%' or b.BusinessName like '%insulat%' or
						b.BusinessName like '%handym%' or b.BusinessName like '%paint%' or b.BusinessName like '%window%' or
						b.BusinessName like '%electric%' or b.BusinessName like '%plumb%' or b.BusinessName like '%waterproof%' or
						b.BusinessName like '%gutter%' or b.BusinessName like '%concrete%' or b.BusinessName like '%deck%' or
						b.BusinessName like '%patio%' or b.BusinessName like '%tile%' or b.BusinessName like '%brick%' or
						b.BusinessName like '%paving%' or b.BusinessName like '%chimney%' or b.BusinessName like '%pressure wash%' or
						b.BusinessName like '%power wash%' or b.BusinessName like '%pressure clean%' or b.BusinessName like '%kitchen%' or
						b.BusinessName like '%bathroom%' or b.BusinessName like '%solar%' or b.BusinessName like '%landscap%' or
						b.BusinessName like '%cabinet%' or b.BusinessName like '%masonry%' or b.BusinessName like '% tree %' or
						b.BusinessName like '%heating%' or b.BusinessName like '%homes%' or b.BusinessName like '%pools%' or
						b.BusinessName like '%garage%' or b.BusinessName like '%door%' or b.BusinessName like '%porch%' or
						b.BusinessName like '%sealing%' or b.BusinessName like '%coating%' or b.BusinessName like '%staining%' or
						b.BusinessName like '%floor%' or b.BusinessName like '%stone%' or b.BusinessName like '%framing%' or
						b.BusinessName like '%air conditioning%' or b.BusinessName like '%counter%' or b.BusinessName like '%shelters%' or
						b.BusinessName like '%mirror%' or b.BusinessName like '%sandblast%' or b.BusinessName like '%marble%' or
						b.BusinessName like '%inspection%' or b.BusinessName like '% log %' or b.BusinessName like '%excavat%' or
						b.BusinessName like '%drywall%' or b.BusinessName like '%hardwood%' or b.BusinessName like '%refrigerat%' or
						b.BusinessName like '%shutter%' or b.BusinessName like '% frame %' or b.BusinessName like '%woodwork%' or
						b.BusinessName like '%granite%' or b.BusinessName like '%aluminum%' or b.BusinessName like '%stucco%' or
						b.BusinessName like '%welding%' or b.BusinessName like '%basement%' or b.BusinessName like '%fence%' or
						b.BusinessName like '%exterminat%' or b.BusinessName like '%cleaning%' or b.BusinessName like '%foundation%' or
						b.BusinessName like '%fire protection%' or b.BusinessName like '% duct%' or b.BusinessName like '%engineer%' or
						b.BusinessName like '%abatement%' or b.BusinessName like '% pool %' or b.BusinessName like '%fumigat%' or
						b.BusinessName like '%carport%' or b.BusinessName like '%awning%' or b.BusinessName like '%cooling%' or
						b.BusinessName like '%marine%' or b.BusinessName like '%millwork%' or b.BusinessName like '%plaster%' or
						b.BusinessName like '%fencing%' or b.BusinessName like '%septic%' or b.BusinessName like '%custom build%' or
						b.BusinessName like '%handy m%' or b.BusinessName like '%termite%'
					)
				) or
				/* specialized medical classified as general medical */
				(
					(b.TOBID = '60971-000' or b.TOBID = '60429-200') and t2.TOBID is null and
					(
						b.BusinessName like '%surge%' or b.BusinessName like '%dental%' or b.BusinessName like '%dentist%' or
						b.BusinessName like '%chiro%' or b.BusinessName like '%acupunct%' or b.BusinessName like '%holsitic%' or
						b.BusinessName like '%gastro%' or b.BusinessName like '%endocrin%' or b.BusinessName like '%ophtha%' or
						b.BusinessName like '%anesth%' or b.BusinessName like '%psych%' or b.BusinessName like '%urolog' or
						b.BusinessName like '%oncol%' or b.BusinessName like '%osteo%' or b.BusinessName like '%radiol%' or
						b.BusinessName like '%cardiol%' or b.BusinessName like '%gyn%' or b.BusinessName like '%dermat%' or
						b.BusinessName like '%internal med%' or b.BusinessName like '%ear, nose%' or b.BusinessName like '%ear nose%' or
						b.BusinessName like '%ortho%' or b.BusinessName like '%pedia%' or b.BusinessName like '%obste%' or
						b.BusinessName like '%pulmon%' or b.BusinessName like '%hormon%' or b.BusinessName like '%sports%' or
						b.BusinessName like '%nephro%' or b.BusinessName like '%maxill%' or b.BusinessName like '%imaging%' or
						b.BusinessName like '%diagnos%' or b.BusinessName like '%physical therap%' or b.BusinessName like '%hypno%' or
						b.BusinessName like '%myo%' or b.BusinessName like '%cryo%' or b.BusinessName like '%biofeedback%' or
						b.BusinessName like '%colon%' or b.BusinessName like '%massage%' or b.BusinessName like '%hydro%' or
						b.BusinessName like '%occupational%' or b.BusinessName like '%laser%' or b.BusinessName like '%aroma%' or
						b.BusinessName like '%oxygen%' or b.BusinessName like '%autis%' or b.BusinessName like '%reiki%' or
						b.BusinessName like '%equipment%' or b.BusinessName like '%supplies%' or b.BusinessName like '%supply%' or
						b.BusinessName like '%nutrition%' or b.BusinessName like '%speech%' or b.BusinessName like '%respir%' or
						b.BusinessName like '%fitness%' or b.BusinessName like '%emergency%' or b.BusinessName like '%clinic,%' or
						b.BusinessName like '%clinic' or b.BusinessName like '%clinics' or b.BusinessName like '%clinic %' or
						b.BusinessName like '%rehab%' or b.BusinessName like '%rheum%' or b.BusinessName like '%arthrit%' or
						b.BusinessName like '%allerg%' or b.BusinessName like '%asthma%' or b.BusinessName like '%testing%' or
						b.BusinessName like '% ent %' or b.BusinessName like '% ent' or b.BusinessName like 'ent %' or
						b.BusinessName like '%laborator%' or b.BusinessName like '%labs%' or b.BusinessName like '%lab %' or
						b.BusinessName like '%lab' or b.BusinessName like '%optical%' or b.BusinessName like '%instrument%' or
						b.BusinessName like '%research%' or b.BusinessName like '%cardiac%' or b.BusinessName like '%vasc%' or
						b.BusinessName like '%birth%' or b.BusinessName like '%midwife%' or b.BusinessName like '%midwive%' or
						b.BusinessName like '%spine%' or b.BusinessName like '%spinal%' or b.BusinessName like '%athlet%' or
						b.BusinessName like '%pain%' or b.BusinessName like '%hospital%' or b.BusinessName like '%ambul%' or
						b.BusinessName like '%hospice%' or b.BusinessName like '%home%' or b.BusinessName like '%nurs%' or
						b.BusinessName like '%blood%' or b.BusinessName like '%cancer%' or b.BusinessName like '%phleb%' or
						b.BusinessName like '%family%' or b.BusinessName like '%ultrasound%' or b.BusinessName like '%natal%' or
						b.BusinessName like '%diabet%' or b.BusinessName like '%preg%' or b.BusinessName like '%cosmetic%' or
						b.BusinessName like '%urgent%' or b.BusinessName like '%staff%' or b.BusinessName like '%consult%' or
						b.BusinessName like '%personnel%' or b.BusinessName like '%mri%' or b.BusinessName like '% scan%' or
						b.BusinessName like '%joint%' or b.BusinessName like '%foot%' or b.BusinessName like '%child%' or
						b.BusinessName like '%breast%' or b.BusinessName like '%neck%' or b.BusinessName like '%laryn%' or
						b.BusinessName like '%scooter%' or b.BusinessName like '%wheelchair%' or b.BusinessName like '%diet%' or
						b.BusinessName like '%dialy%' or b.BusinessName like '%electrol%' or b.BusinessName like '%lasik%' or
						b.BusinessName like '% vision%' or b.BusinessName like 'vision %' or b.BusinessName like '%weight%' or
						b.BusinessName like '% eye%' or b.BusinessName like 'eye%' or b.BusinessName like '%lung%' or
						b.BusinessName like '%sleep %' or b.BusinessName like '%sleep' or b.BusinessName like '%counsel%' or
						b.BusinessName like '% mental%' or b.BusinessName like 'mental%' or b.BusinessName like '%immediate%' or
						b.BusinessName like '%herbal%' or b.BusinessName like '%herbs%' or b.BusinessName like '%insurance%' or
						b.BusinessName like '%billing%' or b.BusinessName like '%data%' or b.BusinessName like '%yoga%' or
						b.BusinessName like '%urology%' or b.BusinessName like '%pharma%' or b.BusinessName like '%dds%' or
						b.BusinessName like '%d.d.s.%' or b.BusinessName like '%dmd%' or b.BusinessName like '%d.m.d.%' or
						b.BusinessName like '%assisted%' or b.BusinessName like '%cpr%' or b.BusinessName like '%visit%' or
						b.BusinessName like '%osteo%' or b.BusinessName like '%audiol%'
					)
				) or
				/* housekeepers classified as janitors */
				(
					b.TOBID = '60478-000' and t2.TOBID is null and
					(
						' ' + b.BusinessName like '% house%' or ' ' + b.BusinessName like '% home%' or b.BusinessName like '%maid%' or
						b.BusinessName like '%chimney%' or b.BusinessName like '%power wash%' or b.BusinessName like '%pressure wash%' or
						b.BusinessName like '%pressure clean%'
					) and
					b.BusinessName not like '%janitor%' and
					b.BusinessName not like '%office%' and
					b.BusinessName not like '%commercial%' and
					b.BusinessName not like '%building%' and
					b.BusinessName not like '%warehouse%'
				)

			)
		";
	$conn->execute($insert);

	return $insert;
}

function RetrieveBBBs() {
	global $conn;

	$query = "
		SELECT
			'BBB ' + BBB.NicknameCity,
			BBB.BBBID,
			(select count(*) from BadBusinessTOBID bt WITH (NOLOCK) where bt.BBBID = BBB.BBBID)
		FROM BBB WITH (NOLOCK)
		WHERE
			BBB.IsActive = '1' and BBB.BBBBranchID = '0'
		ORDER BY BBB.NickNameCity
		";
	$rawbbbs = $conn->execute($query);
	$bbbs = $rawbbbs->GetArray();
	foreach ($bbbs as $k => $fields) {
		$oBBB = $fields[0];
		$oBBBID = $fields[1];
		$oCount = $fields[2];
		$result[] = [
			'oBBB' => $oBBB,
			'oBBBID' => $oBBBID,
			'oCount' => $oCount
		];
	}
	return json_encode($result);
}

?>