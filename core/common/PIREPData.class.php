<?php
/**
 * phpVMS - Virtual Airline Administration Software
 * Copyright (c) 2008 Nabeel Shahzad
 * For more information, visit www.phpvms.net
 *	Forums: http://www.phpvms.net/forum
 *	Documentation: http://www.phpvms.net/docs
 *
 * phpVMS is licenced under the following license:
 *   Creative Commons Attribution Non-commercial Share Alike (by-nc-sa)
 *   View license.txt in the root, or visit http://creativecommons.org/licenses/by-nc-sa/3.0/
 *
 * @author Nabeel Shahzad
 * @copyright Copyright (c) 2008, Nabeel Shahzad
 * @link http://www.phpvms.net
 * @license http://creativecommons.org/licenses/by-nc-sa/3.0/
 */

class PIREPData
{
	/**
	 * Return all of the pilot reports. Can pass a start and
	 * count for pagination. Returns 20 rows by default. If you
	 * only want to return the latest n number of reports, use
	 * GetRecentReportsByCount()
	 */
	public static function GetAllReports($start=0, $count=20)
	{
		$sql = 'SELECT p.pirepid, u.pilotid, u.firstname, u.lastname, u.email, u.rank,
						p.code, p.flightnum, p.depicao, p.arricao, p.flighttime, 
						a.name as aircraft, a.registration,
						p.distance, UNIX_TIMESTAMP(p.submitdate) as submitdate, p.accepted, p.log
					FROM '.TABLE_PREFIX.'pilots u, '.TABLE_PREFIX.'pireps p
						INNER JOIN '.TABLE_PREFIX.'aircraft a ON a.id = p.aircraft
					WHERE p.pilotid=u.pilotid LIMIT '.$start.', '.$count;

		return DB::get_results($sql);
	}
	

	/**
	 * Get all of the reports by the accepted status. Use the
	 * constants:
	 * PIREP_PENDING, PIREP_ACCEPTED, PIREP_REJECTED,PIREP_INPROGRESS
	 */
	public static function GetAllReportsByAccept($accept=0)
	{
		$sql = 'SELECT p.pirepid, u.pilotid, u.firstname, u.lastname, u.email, u.rank,
						p.code, p.flightnum, p.depicao, p.arricao, p.flighttime, 
						a.name as aircraft, a.registration,
						p.distance, UNIX_TIMESTAMP(p.submitdate) as submitdate, p.accepted, p.log
					FROM '.TABLE_PREFIX.'pilots u, '.TABLE_PREFIX.'pireps p
						INNER JOIN '.TABLE_PREFIX.'aircraft a ON a.id = p.aircraft
					WHERE p.pilotid=u.pilotid AND p.accepted='.$accept;

		return DB::get_results($sql);
	}
	
	public static function GetAllReportsFromHub($accept=0, $hub)
	{
		$sql = "SELECT p.pirepid, u.pilotid, u.firstname, u.lastname, u.email, u.rank,
						p.code, p.flightnum, p.depicao, p.arricao, p.flighttime, 
						a.name as aircraft, a.registration,
						p.distance, UNIX_TIMESTAMP(p.submitdate) as submitdate, p.accepted, p.log
					FROM ".TABLE_PREFIX."pilots u, ".TABLE_PREFIX."pireps p
						INNER JOIN '.TABLE_PREFIX.'aircraft a ON a.id = p.aircraft
					WHERE p.pilotid=u.pilotid AND p.accepted=$accept
						AND u.hub='$hub'";

		return DB::get_results($sql);
	}

	/**
	 * Get the latest reports that have been submitted,
	 * return the last 10 by default
	 */
	public static function GetRecentReportsByCount($count = 10)
	{
		if($count == '') $count = 10;

		$sql = 'SELECT p.pirepid, u.pilotid, u.firstname, u.lastname, u.email, u.rank,
					   p.code, p.flightnum, p.depicao, p.arricao, p.flighttime, 
					   a.name as aircraft, a.registration,
					   p.distance, UNIX_TIMESTAMP(p.submitdate) as submitdate, p.accepted, p.log
					FROM '.TABLE_PREFIX.'pilots u, '.TABLE_PREFIX.'pireps p
						INNER JOIN '.TABLE_PREFIX.'aircraft a ON a.id = p.aircraft
					WHERE p.pilotid=u.pilotid
					ORDER BY p.submitdate DESC
					LIMIT '.intval($count);

		return DB::get_results($sql);
	}

	/**
	 * Get the latest reports by n number of days
	 */
	public static function GetRecentReports($days=2)
	{
		$sql = 'SELECT p.pirepid, u.pilotid, u.firstname, u.lastname, u.email, u.rank,
					   p.code, p.flightnum, p.depicao, p.arricao, p.flighttime, 
					   a.name as aircraft, a.registration,
					   p.distance, UNIX_TIMESTAMP(p.submitdate) as submitdate, p.accepted, p.log
					FROM '.TABLE_PREFIX.'pilots u, '.TABLE_PREFIX.'pireps p
						INNER JOIN '.TABLE_PREFIX.'aircraft a ON a.id = p.aircraft
					WHERE p.pilotid=u.pilotid
						AND DATE_SUB(CURDATE(), INTERVAL '.$days.' DAY) <= p.submitdate
					ORDER BY p.submitdate DESC';

		return DB::get_results($sql);
	}

	/**
	 * Get the number of reports on a certain date
	 *  Pass unix timestamp for the date
	 */
	public static function GetReportCount($date)
	{
		$sql = 'SELECT COUNT(*) AS count FROM '.TABLE_PREFIX.'pireps
					WHERE DATE(submitdate)=DATE(FROM_UNIXTIME('.$date.'))';

		$row = DB::get_row($sql);
		return $row->count;
	}
	
	/**
	 * Get the number of reports on a certain date, for a certain route
	 */
	public static function GetReportCountForRoute($code, $flightnum, $date)
	{
		$sql = "SELECT COUNT(*) AS count FROM ".TABLE_PREFIX."pireps
					WHERE DATE(submitdate)=DATE(FROM_UNIXTIME($date))
						AND code='$code' AND flightnum='$flightnum'";

		$row = DB::get_row($sql);
		return $row->count;
	}

	/**
	 * Get the number of reports for the last x  number of days
	 * Returns 1 row for every day, with the total number of
	 * reports per day
	 */
	public static function GetCountsForDays($days = 7)
	{
		$sql = 'SELECT DISTINCT(DATE(submitdate)) AS submitdate,
					(SELECT COUNT(*) FROM '.TABLE_PREFIX.'pireps WHERE DATE(submitdate)=DATE(p.submitdate)) AS count
				FROM '.TABLE_PREFIX.'pireps p WHERE DATE_SUB(CURDATE(), INTERVAL 7 DAY) <= p.submitdate';

		return DB::get_results($sql);
	}

	/**
	 * Get all of the reports for a pilot. Pass the pilot id
	 * The ID is their database ID number, not their airline ID number
	 */
	public static function GetAllReportsForPilot($pilotid)
	{
		/*$sql = 'SELECT pirepid, pilotid, code, flightnum, depicao, arricao, aircraft,
					   flighttime, distance, UNIX_TIMESTAMP(submitdate) as submitdate, accepted
					FROM '.TABLE_PREFIX.'pireps';*/
		$sql = 'SELECT p.pirepid, u.firstname, u.lastname, u.email, u.rank,
						dep.name as depname, dep.lat AS deplat, dep.lng AS deplong,
						arr.name as arrname, arr.lat AS arrlat, arr.lng AS arrlong,
					    p.code, p.flightnum, p.depicao, p.arricao, 
						a.id as aircraftid, a.name as aircraft, a.registration, p.flighttime,
					   p.distance, UNIX_TIMESTAMP(p.submitdate) as submitdate, p.accepted, p.log
					FROM '.TABLE_PREFIX.'pilots u, '.TABLE_PREFIX.'pireps p
						INNER JOIN '.TABLE_PREFIX.'airports AS dep ON dep.icao = p.depicao
						INNER JOIN '.TABLE_PREFIX.'airports AS arr ON arr.icao = p.arricao
						INNER JOIN '.TABLE_PREFIX.'aircraft a ON a.id = p.aircraft
					WHERE p.pilotid=u.pilotid AND p.pilotid='.intval($pilotid).'
					ORDER BY p.submitdate DESC';

		return DB::get_results($sql);
	}

	/**
	 * Change the status of a PIREP. For the status, use the
	 * constants:
	 * PIREP_PENDING, PIREP_ACCEPTED, PIREP_REJECTED,PIREP_INPROGRESS
	 */
	public static function ChangePIREPStatus($pirepid, $status)
	{
		$sql = 'UPDATE '.TABLE_PREFIX.'pireps
					SET accepted='.$status.' WHERE pirepid='.$pirepid;

		return DB::query($sql);
	}

	/**
	 * Get all of the details for a PIREP, including lat/long of the airports
	 */
	public static function GetReportDetails($pirepid)
	{
		$sql = 'SELECT p.pirepid, u.pilotid, u.firstname, u.lastname, u.email, u.rank,
						dep.name as depname, dep.lat AS deplat, dep.lng AS deplong,
						arr.name as arrname, arr.lat AS arrlat, arr.lng AS arrlong,
					    p.code, p.flightnum, p.depicao, p.arricao, 
					    a.id as aircraftid, a.name as aircraft, a.registration, p.flighttime,
					    p.distance, UNIX_TIMESTAMP(p.submitdate) as submitdate, p.accepted, p.log
					FROM '.TABLE_PREFIX.'pilots u, '.TABLE_PREFIX.'pireps p
						INNER JOIN '.TABLE_PREFIX.'airports AS dep ON dep.icao = p.depicao
						INNER JOIN '.TABLE_PREFIX.'airports AS arr ON arr.icao = p.arricao
						LEFT JOIN '.TABLE_PREFIX.'aircraft a ON a.id = p.aircraft
					WHERE p.pilotid=u.pilotid AND p.pirepid='.$pirepid;

		return DB::get_row($sql);
	}

	/**
	 * Get the latest reports for a pilot
	 */
	public static function GetLastReports($pilotid, $count = 1, $status='')
	{
		$sql = 'SELECT * FROM '.TABLE_PREFIX.'pireps
					WHERE pilotid='.intval($pilotid);
					
		# Check it via the status
		if($status != '')
		{
			$sql .= ' AND accepted='.$status;
		}
		
		$sql .=' ORDER BY submitdate DESC
					LIMIT '.intval($count);

		if($count == 1)
			return DB::get_row($sql);
		else
			return DB::get_results($sql);
	}

	/**
	 * Get a pilot's reports by the status.  Use the
	 * constants:
	 * PIREP_PENDING, PIREP_ACCEPTED, PIREP_REJECTED, PIREP_INPROGRESS
	 */
	public static function GetReportsByAcceptStatus($pilotid, $accept=0)
	{

		$sql = 'SELECT * FROM '.TABLE_PREFIX.'pireps
					WHERE pilotid='.intval($pilotid).' AND accepted='.intval($accept);

		return DB::get_results($sql);
	}
	

	/**
	 * Get all of the comments for a pilot report
	 */
	public static function GetComments($pirepid)
	{
		$sql = 'SELECT c.comment, UNIX_TIMESTAMP(c.postdate) as postdate,
						p.firstname, p.lastname
					FROM '.TABLE_PREFIX.'pirepcomments c, '.TABLE_PREFIX.'pilots p
					WHERE p.pilotid=c.pilotid AND c.pirepid='.$pirepid.'
					ORDER BY postdate ASC';

		return DB::get_results($sql);
	}
	
	/**
	 * File a PIREP
	 */
	public static function FileReport($pirepdata)
	{
		
		/*$pirepdata = array('pilotid'=>'',
					  'code'=>'',
					  'flightnum'=>'',
					  'leg'=>'',
					  'depicao'=>'',
					  'arricao'=>'',
					  'aircraft'=>'',
					  'flighttime'=>'',
					  'submitdate'=>'',
					  'comment'=>'',
					  'log'=>'');*/
		
		if($pirepdata['leg'] == '') $pirepdata['leg'] = 1;
		$pirepdata['log'] = DB::escape($pirepdata['log']);
		
		if($pirepdata['depicao'] == '' || $pirepdata['arricao'] == '')
		{
			return false;
		}
		
		# Remove the comment field, since it doesn't exist
		# 	in the PIREPs table.
		$comment = DB::escape($pirepdata['comment']);
		//@unset($pirepdata['comment']);
		
		#replaced
		$sql = "INSERT INTO ".TABLE_PREFIX."pireps
					(pilotid, code, flightnum, depicao, arricao, aircraft, flighttime, submitdate, accepted, log)
					VALUES ($pirepdata[pilotid], '$pirepdata[code]', '$pirepdata[flightnum]', 
							'$pirepdata[depicao]', '$pirepdata[arricao]', '$pirepdata[aircraft]', 
							'$pirepdata[flighttime]', NOW(), ".PIREP_PENDING.", '$pirepdata[log]')";

		$ret = DB::query($sql);
		$pirepid = DB::$insert_id;

		// Add the comment if its not blank
		if($comment != '')
		{
			$pirepid = DB::$insert_id;
			$sql = "INSERT INTO ".TABLE_PREFIX."pirepcomments (pirepid, pilotid, comment, postdate)
						VALUES ($pirepid, $pirepdata[pilotid], '$pirepdata[comment]', NOW())";

			$ret = DB::query($sql);

		}

		DB::$insert_id = $pirepid;
		
		# Do other assorted tasks that are along with a PIREP filing
		# Update the flown count for that route
		SchedulesData::IncrementFlownCount($code, $flightnum);
		self::UpdatePIREPFeed();
		
		return true;
	}
	
	public static function UpdateFlightReport($pirepid, $pirepdata)
	{		
		/*$pirepdata = array('pilotid'=>'',
					  'code'=>'',
					  'flightnum'=>'',
					  'leg'=>'',
					  'depicao'=>'',
					  'arricao'=>'',
					  'aircraft'=>'',
					  'flighttime'=>'',
					  'submitdate'=>'',
					  'comment'=>'',
					  'log'=>'');*/
		
		if($pirepdata['leg'] == '') $pirepdata['leg'] = 1;		
		if($pirepdata['depicao'] == '' || $pirepdata['arricao'] == '')
		{
			return false;
		}
		
		
		$sql = "UPDATE ".TABLE_PREFIX."pireps SET
						code='$pirepdata[code]', flightnum='$pirepdata[flightnum]',
						depicao='$pirepdata[depicao]', arricao='$pirepdata[arricao]', 
						aircraft='$pirepdata[aircraft]', flighttime='$pirepdata[flighttime]'
					WHERE pirepid=$pirepid";

		$ret = DB::query($sql);
		
		return true;
	}
	
	public static function UpdatePIREPFeed()
	{
		# Load PIREP into RSS feed
		$reports = PIREPData::GetRecentReportsByCount(10);
		$rss = new RSSFeed('Latest Pilot Reports', SITE_URL, 'The latest pilot reports');
		
		foreach($reports as $report)
		{
			$rss->AddItem('Report #'.$report->pirepid.' - '.$report->depicao.' to '.$report->arricao,
							SITE_URL.'/admin/index.php?admin=viewpending','',
							'Filed by '.PilotData::GetPilotCode($report->code, $report->pilotid) 
							. " ($report->firstname $report->lastname)");
		}
		
		$rss->BuildFeed(LIB_PATH.'/rss/latestpireps.rss');
	}
	
	/** 
	 * Append to a flight report's log
	 */
	 
	public static function AppendToLog($pirepid, $log)
	{
		$sql = 'UPDATE '.TABLE_PREFIX.'pireps 
					SET `log` = CONCAT(`log`, \''.$log.'\')
					WHERE pirepid='.$pirepid;
					
		$res = DB::query($sql);
		
		if(DB::errno() != 0)
			return false;
		
		return true;		
	}

	/**
	 * Add a comment to the flight report
	 */
	public static function AddComment($pirepid, $commenter, $comment)
	{
		$sql = "INSERT INTO ".TABLE_PREFIX."pirepcomments (pirepid, pilotid, comment, postdate)
					VALUES ($pirepid, $commenter, '$comment', NOW())";

		$res = DB::query($sql);
		
		if(DB::errno() != 0)
			return false;
			
		return true;
	}
	
	
	public static function GetAllFields()
	{
		return DB::get_results('SELECT * FROM '.TABLE_PREFIX.'pirepfields');
	}
	
	/**
	 * Get all of the "cusom fields" for a pirep
	 */
	public static function GetFieldData($pirepid)
	{
		$sql = 'SELECT f.title, f.name, v.value
					FROM '.TABLE_PREFIX.'pirepfields f
					LEFT JOIN '.TABLE_PREFIX.'pirepvalues v
						ON f.fieldid=v.fieldid 
							AND v.pirepid='.intval($pirepid);
					
		return DB::get_results($sql);
	}
	
	public static function GetFieldInfo($id)
	{
		$sql = 'SELECT * FROM '.TABLE_PREFIX.'pirepfields
					WHERE fieldid='.$id;
		
		return DB::get_row($sql);
	}
	
	public static function GetFieldValue($fieldid, $pirepid)
	{
		$sql = 'SELECT * FROM '.TABLE_PREFIX.'pirepvalues
					WHERE fieldid='.$fieldid.' AND pirepid='.$pirepid;
		
		$ret = DB::get_row($sql);
		return $ret->value;
	}
	/**
	 * Add a custom field to be used in a PIREP
	 */
	public static function AddField($title, $type='', $values='')
	{
		$fieldname = strtoupper(str_replace(' ', '_', $title));
		//$values = DB::escape($values);
		
		if($type == '')
			$type = 'text';
				
		$sql = "INSERT INTO " . TABLE_PREFIX ."pirepfields (title, name, type, options)
					VALUES ('$title', '$fieldname', '$type', '$values')";
	
		$res = DB::query($sql);
		
		if(DB::errno() != 0)
			return false;
			
		return true;
	}
	
	/**
	 * Edit the field
	 */
	public static function EditField($id, $title, $type, $values='')
	{
		$fieldname = strtoupper(str_replace(' ', '_', $title));
		
		$sql = "UPDATE ".TABLE_PREFIX."pirepfields
					SET title='$title',name='$fieldname', type='$type', options='$values'
					WHERE fieldid=$id";
				
		$res = DB::query($sql);
		
		if(DB::errno() != 0)
			return false;
			
		return true;
	}
	
	/**
	 * Save PIREP fields
	 */
	public static function SaveFields($pirepid, $list)
	{
		$allfields = self::GetAllFields();
		
		if(!$allfields) return true;
			
		foreach($allfields as $field)
		{
			// See if that value already exists
			$sql = 'SELECT id FROM '.TABLE_PREFIX.'pirepvalues
						WHERE fieldid='.$field->fieldid.' AND pirepid='.$pirepid;
			$res = DB::get_row($sql);

			$fieldname =str_replace(' ', '_', $field->name);
			$value = $list[$fieldname];
			
			if($res)
			{
				$sql = 'UPDATE '.TABLE_PREFIX."pirepvalues
							SET value='$value'
							WHERE fieldid=$field->fieldid
								AND pirepid=$pirepid";
			}
			else
			{		
				$sql = "INSERT INTO ".TABLE_PREFIX."pirepvalues
						(fieldid, pirepid, value)
						VALUES ($field->fieldid, $pirepid, '$value')";
			}
						
			DB::query($sql);
		}
		
		return true;
	}
		
	public static function DeleteField($id)
	{
		$sql = 'DELETE FROM '.TABLE_PREFIX.'pirepfields WHERE fieldid='.$id;

		$res = DB::query($sql);
		
		if(DB::errno() != 0)
			return false;
			
		return true;

		//TODO: delete all of the field values!
		//$sql = 'DELETE FROM '.TABLE_PREFIX.'
	}

	
	/**
	 * Show the graph of the past week's reports. Outputs the
	 *	image unless $ret == true
	 */
	public static function ShowReportCounts($ret=false)
	{
		// Recent PIREP #'s
		$max = 0;
		$data = '[';

		// This is for the past 7 days
		for($i=-7;$i<=0;$i++)
		{
			$date = mktime(0,0,0,date('m'), date('d') + $i ,date('Y'));
			$count = PIREPData::GetReportCount($date);

			//array_push($data, intval($count));
			//$label .= date('m/d', $date) .'|';
			$data.=$count.',';
			if($count > $max)
				$max = $count;
		}
		
		$data = substr($data, 0, strlen($data)-1);
		$data .= ']';
		
		return $data;
		/*$chart = new googleChart($data);
		$chart->dimensions = '700x200';
		$chart->setLabelsMinMax($max,'left');
		$chart->setLabels($label,'bottom');

		if($ret == true)
			return $chart->draw(false);
		else
			echo '<img src="'.$chart->draw(false).'" align="center" />';*/
	}
	
}

?>