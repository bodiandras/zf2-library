<?php
namespace Library;
defined('SERVER_PATH') || define('SERVER_PATH' , '/www');

class WordPress
{
    protected $sm;
    protected $baseUrl;
    
    public function __construct($sm)
    {        
        $this->sm = $sm;
        $config = $sm->get('config');            
        $this->baseUrl = $config['wordpress']['url'];
        require_once SERVER_PATH.'/wordpress/wp-load.php';
    }
    
    public function getPosts($args)
    {
        $postsArray = get_posts($args);
        
        $postsFinal = array();
        foreach($postsArray as $_post) {
            $post = array(
                        'title' => $_post->post_title,
                        'link' => $this->getLink($_post->ID),
                        'content' => $_post->post_content,
                        'excerpt' => $_post->post_excerpt,
                        'date' => date("M d, Y", strtotime($_post->post_date)),
            );
            
            $postsFinal []= $post;
            //echo '<pre>'; print_r($_post); echo '</pre>';
            
        }
        return $postsFinal;
    }
    
    public function getLink($postId)
    {
        $articleLink = '/articles/show';
        return str_replace($this->baseUrl, $_SERVER['HTTP_HOST'] . $articleLink, get_permalink($postId));
    }
    
    public function getPost($args)
    {        
        $posts = get_posts($args);        
        $post = $this->formatPost($posts[0]);         
        return $post;
    }
    
    public function latestPosts()
    {
        $numPosts = 3;
        $args = array(
    	'posts_per_page'   => $numPosts,
    	'offset'           => 0,
    	'category'         => '',
    	'orderby'          => 'post_date',
    	'order'            => 'DESC',
    	'include'          => '',
    	'exclude'          => '',
    	'meta_key'         => '',
    	'meta_value'       => '',
    	'post_type'        => 'post',
    	'post_mime_type'   => '',
    	'post_parent'      => '',
    	'post_status'      => 'publish',
    	'suppress_filters' => true );
        
        return $this->getPosts($args);        
    }
    
    public function formatPost($post)
    {        
        //$post->post_content =  nl2br(str_replace(' ', '&nbsp', trim($post->post_content)), true);
        
        $pattern = '/\{(?:[^{}]|(?R))*\}/x';

		preg_match_all($pattern, $post->post_content, $matches);
		//print_r($matches[0]);
		foreach($matches[0] as $m) {			
			$post->post_content = str_replace($m, '', $post->post_content);
		}		                           
        return $post;
    }
}