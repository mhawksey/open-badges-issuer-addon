<?php
/*
Controller name: Mozilla Open Badges Generator
Controller description: Generates Mozilla Open Badges compatible Assertions for the BadgeOS plugin
*/
class JSON_API_Badge_Controller {
	public function assertion() {
		global $json_api;
		$uid_str = $json_api->query->uid;
		$uid = explode ( "-" , $uid_str);
		$post_id = $uid[0];
		$user_id = $uid[2];
		$assertion = array();
		if (isset($post_id)){
			$base_url = home_url().'/'.get_option('json_api_base', 'api');
			$submission = get_post($post_id);
			$salt = wp_salt( 'nonce' );
			$email = BadgeOS_OpenBadgesIssuer::registered_email($user_id);
			$post_type = get_post_type( $post_id );
			
			if ($post_type === "submission" && get_option( 'badgeos_obi_issuer_public_evidence')) {
				$achievement_id = get_post_meta( $post_id, '_badgeos_submission_achievement_id', true );
				$assertion['evidence'] = get_permalink( $post_id );
			} else {
				$achievement_id = $post_id;
			}
			//return badgeos_get_user_achievements();
			$assertion = array_merge(array("uid" => $uid_str,
						  "recipient"=> array(
							"type"=> "email",
							"hashed"=> true,
							"salt"=> $salt,
							"identity"=> 'sha256$' . hash('sha256', $email . $salt)
						  ),
						  "image"=> wp_get_attachment_url( get_post_thumbnail_id($achievement_id) ),
						  "issuedOn"=> strtotime($submission->post_date),
						  "badge"=> $base_url . '/badge/badge_class/?uid=' . $achievement_id ,
						  "verify"=> array(
							"type"=> "hosted",
							"url"=> $base_url .'/badge/assertion/?uid=' . $uid_str
						  )), $assertion);
		}
		return $assertion;
	}
	
	public function badge_class() {
		global $json_api;
		$post_id = $json_api->query->uid;
		if (isset($post_id)){
			$base_url = home_url().'/'.get_option('json_api_base', 'api');
			$badge = get_post($post_id);
			return array ( "name" => $badge->post_title,
  						   "description" => ($badge->post_content) ? html_entity_decode(strip_tags($badge->post_content), ENT_QUOTES, 'UTF-8') : "",
  						   "image" => wp_get_attachment_url( get_post_thumbnail_id( $post_id )),
  						   "criteria" => get_permalink( $post_id ),
  						   "issuer"=>  $base_url.'/badge/issuer/' );
		}
	}
	public function issuer() {
		$issuerFields = array('description',
							  'image',
							  'email',
							  'revocationList');
							  
		
		$issuer = array("name" => ($org_name = get_option( 'badgeos_obi_issuer_org_name')) ? $org_name : get_bloginfo( 'name', 'display' ),
						"url" =>  ($org_url = get_option( 'badgeos_obi_issuer_org_url')) ? $org_url : home_url());
		
		foreach($issuerFields as $field){
			$val = get_option( 'badgeos_obi_issuer_org_'.$field);
			if (!empty($val)){
				$issuer[$field] = $val;	
			}
		}
		
		return $issuer;
	}
	public function achievements() {
		global $blog_id, $json_api;
		
		$type = badgeos_get_achievement_types_slugs();
		// Drop steps from our list of "all" achievements
		$step_key = array_search( 'step', $type );
		if ( $step_key ){
			unset( $type[$step_key] );
		}
		$type[] = 'submission';
		
		//$user_id = get_current_user_id();
		// Get the current user if one wasn't specified
		if( ! $user_id ){
			if ($json_api->query->user_id){
				$user_id = $json_api->query->user_id;
			} else {
				return array("message" => "No user_id"); 	
			}
		}
		// Get submissions
		$args = array(
			'post_type'      =>	'submission',
			'posts_per_page'   => -1,
			'author' => $user_id,
			'post_status'    => 'publish',
			'fields' => 'ids'
		);
		$sub_arg = $args;
		$submissions = get_posts($args);
		$hidden = badgeos_get_hidden_achievement_ids( $type );
	
		// Initialize our output and counters
		$achievements = array();
		$achievement_count = 0;
		
		// Grab our earned badges (used to filter the query)
		$earned_ids = badgeos_get_user_earned_achievement_ids( $user_id, $type );
		$earned_ids = array_map('intval', $earned_ids);
		// Query Achievements
		$args = array(
			'post_type'      =>	$type,
			'posts_per_page' =>	-1,
			'post_status'    => 'publish',
		);
		$args[ 'post__in' ] = array_merge( array( 0 ), $earned_ids);
		
		$exclude = array();
		// exclude badges which are submissions
		if ( !empty( $submissions ) ) {
			foreach ($submissions as $sub_id){
				$exclude[] = absint(get_post_meta( $sub_id, '_badgeos_submission_achievement_id', true ));
				$args[ 'post__in' ][] = $sub_id;
			}
			$args[ 'post__in' ] = array_diff($args[ 'post__in' ], $exclude);
		}
		
		// Loop Achievements
		$achievement_posts = new WP_Query( $args );
		$query_count += $achievement_posts->found_posts;
		$base_url = site_url().'/'.get_option('json_api_base', 'api').'/badge/assertion/?uid=';
		$pushed_badges = ( $pushed_items = get_user_meta( absint( $user_id ), '_badgeos_backpack_pushed' ) ) ? (array) $pushed_items : array();
		while ( $achievement_posts->have_posts() ) : $achievement_posts->the_post();
			$achievement_id = get_the_ID();
			if (!in_array($achievement_id , $hidden)){
				$uid = $achievement_id . "-" . get_post_time('U', true) . "-" . $user_id;
				$button_text = (!in_array($base_url.$uid, $pushed_badges)) ? __( 'Send to Mozilla Backpack', 'badgeos_obi_issuer' ) : __( 'Resend to Mozilla Backpack', 'badgeos_obi_issuer' ); 
				$badge_html = (get_post_type() === 'submission') ?  badgeos_render_achievement(get_post_meta( get_the_ID(), '_badgeos_submission_achievement_id', true )) : badgeos_render_achievement($achievement_id);
				$badge_html .= '<div class="badgeos_backpack_action">';
				$badge_html .= '<a href="" class="badgeos_backpack button" data-uid="'.$base_url.$uid.'">'.$button_text.'</a> ';
				$badge_html .= '<input type="checkbox" value="'.$base_url.$uid.'" name="badgeos_backpack_issues[]" />';
				$badge_html .= '</div>';
				$achievements[] = array("uid" => $base_url.$uid,
										"type" => get_post_type($achievement_id),
										"data" => $badge_html);
				$achievement_count++;
			}
		endwhile;
		
		return array ( "achievements" => $achievements,
					   "count" => $achievement_count);
	}
}