<?php

	class q2apro_list_votes_page {
		
		var $directory;
		var $urltoroot;
		
		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
		}
		
		// for display in admin interface under admin/pages
		function suggest_requests() 
		{	
			return array(
				array(
					'title' => 'q2apro List Votes Page', // title of page
					'request' => 'votes', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='votes') {
				return true;
			}

			return false;
		}

		function process_request($request)
		{
		
			/* SETTINGS */
			$numberofposts = 100; // show new users from last x days
			
			// you can set number of users to be shown within the URL
			// e.g. yoursite.com/newusers?last=500
			/*$numberofposts = qa_get("last");
			if(is_null($numberofposts) || $numberofposts<=0) {
				$numberofposts = 50; // max new users to display
			}
			*/
			
			// return if not admin!
			$level = qa_get_logged_in_level();
			if($level<QA_USER_LEVEL_ADMIN) {
				$qa_content = qa_content_prepare();
				$qa_content['custom'] = '<div>'.qa_lang('q2apro_list_votes_lang/access_forbidden').'</div>';
				return $qa_content;
			}
			
			// AJAX post: we received post data, so it should be the ajax call
			$userhandle = qa_post_text('ajaxdata');
			
			if(isset($userhandle)) {
				$userhandle = trim($userhandle);
				
				// find userid
				$userid = qa_db_read_one_value(
								qa_db_query_sub('SELECT userid FROM `^users` 
												WHERE `handle` = #', $userhandle), true);

				if(isset($userid)) {
					// delete all votes from qa_uservotes of this user
					qa_db_query_sub('DELETE FROM `^uservotes` WHERE `userid` = #', $userid);
					
					// DEV: Display votes
					/*$votes = qa_db_read_all_assoc(
								qa_db_query_sub('SELECT postid FROM `^uservotes` 
												WHERE `userid` = #', $userid), true);*/
					
					// do [Recount Posts] and then [Recalculate User Points]					
					echo '<p style="margin:30px 0 60px 0;line-height:200%;"><span style="color:#00F;">Votes successfully removed for user: '.$userhandle.'</span><br />
					Please go to <a href="'.qa_path('admin/stats').'#recount_posts_note">admin>stats</a> and click on <b>Recount Posts</b> and afterwards on <b>Recalculate User Points</b>.</p>';
					
					exit();
				}
				else {
					echo 'Could not find user!';
					exit();
				}
			} // AJAX END
			
			
			// default page
			$qa_content = qa_content_prepare();
			
			// page title
			$qa_content['title'] = $numberofposts.' '.qa_lang('q2apro_list_votes_lang/page_title');

			// initiate output string for table
			$votelisting = '<table> <thead><tr>
								<th>'.qa_lang('q2apro_list_votes_lang/post_date').'</th> 
								<th>'.qa_lang('q2apro_list_votes_lang/vote_from').'</th> 
								<th>'.qa_lang('q2apro_list_votes_lang/vote_for').'</th> 
								<th>'.qa_lang('q2apro_list_votes_lang/vote_point').'</th> 
								<th>'.qa_lang('q2apro_list_votes_lang/vote_item').'</th> 
							</tr></thead>';

			// get last x votes cannot be done chronologic as there is no timestamp on each vote (q2a v1.6.3, table qa_uservotes)
			// so we have to take the last x posts (Q,A) and check them for votes
			$lastvotesQuery = qa_db_query_sub('SELECT postid, userid, vote, flag
								FROM `^uservotes`
								WHERE vote != 0
								ORDER BY `postid` DESC
								LIMIT 0, #', $numberofposts);
			
			while ( ($vote = qa_db_read_one_assoc($lastvotesQuery,true)) !== null ) {
		
				// get postid from vote
				$postid = $vote['postid']; // voted for
				$userid = $vote['userid']; // voter
				$voted = $vote['vote']; // 1 or -1
				
				// get userdata of voter
				$userA = qa_db_read_one_assoc(qa_db_query_sub('SELECT userid,created,handle,avatarblobid,avatarwidth,avatarheight,email,flags
												FROM `^users`
												WHERE userid = #
												LIMIT 1;', $userid));
				$userwhovoted = qa_get_user_avatar_html($userA['flags'], $userA['email'], $userA['handle'], $userA['avatarblobid'], $userA['avatarwidth'], $userA['avatarheight'], qa_opt('avatar_users_size'), false) . ' ' . qa_get_one_user_html($userA['handle'], false);
				
				// get user who received the vote, see postid (can be NULL if anonymous)
				$queryReceiverId = qa_db_read_one_value(qa_db_query_sub('SELECT userid
													FROM `^posts`
													WHERE postid = #
													LIMIT 1;', $postid), true);
				
				// get userdata
				$userwhoreceived = ''; 
				if(isset($queryReceiverId)) {
					$userB = qa_db_read_one_assoc(
									qa_db_query_sub('SELECT userid,created,handle,avatarblobid,avatarwidth,avatarheight,email,flags
												FROM `^users`
												WHERE userid = #
												LIMIT 1;', $queryReceiverId));
					$userwhoreceived = qa_get_user_avatar_html($userB['flags'], $userB['email'], $userB['handle'], $userB['avatarblobid'], $userB['avatarwidth'], $userB['avatarheight'], qa_opt('avatar_users_size'), false) . ' ' . qa_get_one_user_html($userB['handle'], false);
				}
				else {
					$userwhoreceived = qa_lang('main/anonymous');
				}
				
				// get postdata
				$post = qa_db_read_one_assoc(qa_db_query_sub('SELECT type, parentid, title, created
													FROM `^posts`
													WHERE postid = #
													AND upvotes > 0 OR downvotes > 0
													LIMIT 1;', $postid), true);

				$postdate = substr($post['created'],0,10);
				
				if($post['type']=='Q') {
					$qTitle = $post['title'];
					// get correct public URL
					$activity_url = qa_path_html(qa_q_request($postid, $qTitle), null, qa_opt('site_url'), null, null);
					$linkToPost = $activity_url;
					$posthtml = '<a href="'.$linkToPost.'">'.$post['title'].'</a>';
				}
				else if($post['type']=='A') {
					$questionid = $post['parentid'];
					// need to check parent, the question
					$parent = qa_db_read_one_assoc(qa_db_query_sub('SELECT title
													FROM `^posts`
													WHERE postid = #
													AND type = "Q"
													LIMIT 1;', $questionid), true);
					$qTitle = (isset($parent['title'])) ? $parent['title'] : '';
					// get correct public URL
					$activity_url = qa_path_html(qa_q_request($questionid, $qTitle), null, qa_opt('site_url'), null, null);
					$linkToPost = $activity_url.'?show='.$postid.'#a'.$postid;
					$posthtml = '<a href="'.$linkToPost.'">'.$parent['title'].'</a>';
				}
				
				// substr removes seconds
				$votelisting .= '<tr>
					<td>'.$postdate.'</td>
					<td>'.$userwhovoted.'</td>
					<td>'.$userwhoreceived.'</td>
					<td>'.$voted.' </td>
					<td>'.$posthtml.'</td>
					</tr>';
			} // end while
			$votelisting .= '</table>';

			
			// output into theme
			$qa_content['custom'] = '';
			
			$qa_content['custom'] .= '
			<label for="removeuservotes">
				Remove votes of user: 
				<input name="removeuservotes" id="removeuservotes" type="text" /> 
				<button id="submitbtn">Submit</button>
			</label>';
			// container to display ajax return
			$qa_content['custom'] .= '<div id="ajaxresult"></div>';
			
			$qa_content['custom'] .= '<div class="votelistingSt">'. $votelisting .'</div>';
			
			// javascript for ajax requests
			$qa_content['custom'] .= '
			<script type="text/javascript">
				$(document).ready(function(){
					$("#submitbtn").click( function() { 
						doAjaxPost();
					});
					$("#removeuservotes").keyup(function(e) {
						// if enter key
						if(e.which == 13) { 
							doAjaxPost();
						}
					});

					function doAjaxPost() {
						// get postid from input
						var ajax_username = $("#removeuservotes").val(); 
						
						if(ajax_username!="") {
							// send ajax request
							$.ajax({
								 type: "POST",
								 url: "'.qa_self_html().'",
								 data: { ajaxdata: ajax_username },
								 cache: false,
								 success: function(data) {
									//dev
									console.log("server returned:"+data);
									// output result in DIV
									$("#ajaxresult").html( data );
								 },
								 error: function(data) {
									console.log("Ajax error");
									$("#ajaxresult").html( data );
								 }
							});
						}
					}
				});
			</script>
			
			';			
			$qa_content['custom'] .= '<style type="text/css">
				#submitbtn {
					position: relative; 
					overflow: visible; 
					display: inline-block; 
					padding: 5px 12px; 
					text-decoration:none !important;
					border: 1px solid #3072b3;
					border-bottom-color: #2a65a0;
					color:#FFFFFF !important; 
					white-space: nowrap; 
					cursor: pointer; 
					outline: none; 
					background-color: #3C8DDE;
					background-image: linear-gradient(#599bdc, #3072b3);
					border-radius: 0.2em;
				}
				table { 
					width:100%; 
					background:#F5F5F5;
					margin:30px 0 15px;
					text-align:left;
					border-collapse:collapse;
					font-size:12px;
				}
				table th { 
					background-color:#DFD;
					border:1px solid #CCC;
					padding:4px;
				}
				table tr:nth-child(even){ 
					background:#F0F0F0;
				}
				tr:hover { 
					background:#FFC !important;
				}
				td { 
					border:1px solid #CCC;
					padding:1px 10px;
					line-height:25px;
				}
				table td:nth-child(1) { 
					width:10%;
				}
				table td:nth-child(2), table td:nth-child(3) { 
					width:15%;
				}
				table td:nth-child(4) {
					width:5%;
				}
				table td:nth-child(5) {
					width:50%;
				}
				tr td {
					padding:7px 0 7px 5px;
				}
				.votelistingSt {
					display:block;
					font-size:11px;
					line-height:150%;
				}
			</style>';
			
			// if you use this plugin, please leave the credit line - it's a free plugin, give back a bit
			$qa_content['custom'] .= '<a style="font-size:10px;color:#AAA;float:right;" target="blank" href="http://www.q2apro.com/">plugin by q2apro.com</a>';
			
			return $qa_content;
		}
		
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/