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
			$base_url = site_url().'/'.get_option('json_api_base', 'api');
			$submission = get_post($post_id);
			$salt = "0ct3L";
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
			$base_url = site_url().'/'.get_option('json_api_base', 'api');
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
		
		$issuer = array("name" => get_option( 'badgeos_obi_issuer_org_name') ? '' : get_bloginfo( 'name', 'display' ),
						"url" =>  get_option( 'badgeos_obi_issuer_org_url') ? '' : site_url());
		
		foreach($issuerFields as $field){
			$val = get_option( 'badgeos_obi_issuer_org_'.$field);
			if (!empty($val)){
				$issuer[$field] = $val;	
			}
		}
		
		return $issuer;
	}
}