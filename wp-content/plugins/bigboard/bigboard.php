<?php

/**
 * Plugin Name: BigBoard
 * Plugin URI: https://github.com/fromtheoutfit/bigboard-wordpress-plugin
 * Description: Plugin to work with the BigBoard.us API.
 * Version: 1.0
 * Author: Michael Witwicki from The Outfit
 * Author URI: http://fromtheoutfit.com
 * License: GPL2
 */
class bigboard
{

    public function __construct($settings = '')
    {
        $this->options = array(
            'bigboard_api_key'        => array(
                'name'  => 'BigBoard Access Token',
                'type'  => 'input',
                'value' => '',
            ),
            'bigboard_posts_new'      => array(
                'name'  => 'Update BigBoard when posts are created',
                'type'  => 'radio',
                'value' => 'y',
            ),
            'bigboard_posts_updated'  => array(
                'name'  => 'Update BigBoard when posts are updated',
                'type'  => 'radio',
                'value' => 'y',
            ),
            'bigboard_page_new'      => array(
                'name'  => 'Update BigBoard when pages are created',
                'type'  => 'radio',
                'value' => 'y',
            ),
            'bigboard_pages_updated'  => array(
                'name'  => 'Update BigBoard when pages are updated',
                'type'  => 'radio',
                'value' => 'y',
            ),
            'bigboard_posts_comments' => array(
                'name'  => 'Update BigBoard when posts are commented on',
                'type'  => 'radio',
                'value' => 'y',
            ),
        );

        foreach ($this->options as $k => $v)
        {
            $this->options[$k]['value'] = get_option($k);
        }
    }

    /**
     * Publish to BigBoard when a new post is submitted
     *
     * @access public
     * @param int $id
     * @return void
     */

    public function publish_post($id)
    {
        // Get information about the post
        $post   = get_post($id);
        $author = get_user_by('id', $post->post_author);
        $url    = get_permalink($post->ID);

        // New post is published
        if ($this->options['bigboard_posts_new']['value'] == 'y')
        {
            if ($post->post_date == $post->post_modified)
            {
                $this->bigboard_post($author->data->user_email, $post->post_title, 'Post Published', $url);
            }
        }

        // Post is updated
        if ($this->options['bigboard_posts_updated']['value'] == 'y')
        {
            if ($post->post_date !== $post->post_modified)
            {
                $this->bigboard_post($author->data->user_email, $post->post_title, 'Post Updated', $url);
            }
        }
    }

    /**
     * Publish to BigBoard when a new Page is created
     *
     * @access public
     * @param int $id
     * @return void
     */

    public function publish_page($id)
    {
        // Get information about the page
        $post   = get_post($id);
        $author = get_user_by('id', $post->post_author);
        $url    = get_permalink($post->ID);

        // New post is published
        if ($this->options['bigboard_posts_new']['value'] == 'y')
        {
            if ($post->post_date == $post->post_modified)
            {
                $this->bigboard_post($author->data->user_email, $post->post_title, 'Page Published', $url);
            }
        }

        // Post is updated
        if ($this->options['bigboard_posts_updated']['value'] == 'y')
        {
            if ($post->post_date !== $post->post_modified)
            {
                $this->bigboard_post($author->data->user_email, $post->post_title, 'Page Updated', $url);
            }
        }
    }

    /**
     * Publish to BigBoard when a new automatically approved comment is created
     *
     * @access public
     * @param int $id
     * @return void
     */

    public function comment_post($id)
    {
        $comment = get_comment($id);
        $post    = get_post($comment->comment_post_ID);
        $url     = get_permalink($post->ID);

        // New post is published
        if ($this->options['bigboard_posts_comments']['value'] == 'y' && $comment->comment_approved == 1)
        {
            $this->bigboard_post($comment->comment_author_email, $post->post_title, 'Comment Published', $url);
        }
    }

    /**
     * Publish to BigBoard when a new comment is approved
     *
     * @access public
     * @param int $id
     * @return void
     */

    public function wp_set_comment_status($id)
    {
        $comment = get_comment($id);
        $post    = get_post($comment->comment_post_ID);
        $url     = get_permalink($post->ID);

        // New post is published
        if ($this->options['bigboard_posts_comments']['value'] == 'y' && $comment->comment_approved == 1)
        {
            $this->bigboard_post($comment->comment_author_email, $post->post_title, 'Comment Published', $url);
        }
    }

    /**
     * Post to BigBoard
     *
     * @access private
     * @param string $email
     * @param string $summary
     * @param string $label
     * @param string $url
     * @return mixed
     */

    private function bigboard_post($email, $summary, $label, $url)
    {
        $data = '';

        if (isset($this->options['bigboard_api_key']['value']))
        {
            // get it on the board!
            $p['events'][0]['email']   = $email;
            $p['events'][0]['summary'] = $summary;
            $p['events'][0]['label']   = $label;
            $p['events'][0]['url']     = $url;
            $p['events'][0]['time']    = time();

            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Accept: application/json';
            $headers[] = 'X-BigBoard-Token: ' . $this->options['bigboard_api_key']['value'];

            $ch = curl_init();

            $options = array(
                CURLOPT_POST           => 1,
                CURLOPT_URL            => 'https://bigboard.us/api',
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_SSL_VERIFYHOST => FALSE,
                CURLOPT_SSL_VERIFYPEER => FALSE,
                CURLOPT_POSTFIELDS     => json_encode($p),
                CURLOPT_USERAGENT      => 'BigBoard (bigboard@fromtheoutfit.com)',
                CURLOPT_FOLLOWLOCATION => TRUE
            );

            curl_setopt_array($ch, $options);
            $data = json_decode(curl_exec($ch));

        }

        return $data;
    }

    /**
     * Instantiates the plugin options
     *
     * @access public
     * @return void
     */

    public function options_menu()
    {
        add_options_page('BigBoard Options', 'BigBoard', 'manage_options', 'bigboard', array($this, 'options'));
    }

    /**
     * Output the options form
     *
     * @access public
     * @return void
     */

    public function options()
    {
        $data = '<div class="wrap">';

        if (!current_user_can('manage_options'))
        {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // See if the user has posted us some information
        // If they did, this hidden field will be set to 'Y'
        if (isset($_POST['bigboard_hidden_field']) && $_POST['bigboard_hidden_field'] == 'Y')
        {

            // Save the posted value in the database
            foreach ($this->options as $k => $v)
            {
                $this->options[$k]['value'] = $_POST[$k];
                update_option($k, $_POST[$k]);
            }


            // Put an settings updated message on the screen
            $data .= '<div class="updated"><p><strong>Settings Saved</strong></p></div>';
        }

        $data .= '<form name="form1" method="post" action="">
                    <input type="hidden" name="bigboard_hidden_field" value="Y">';

        foreach ($this->options as $k => $v)
        {
            switch ($v['type'])
            {
                case 'input':
                    $data .= '<p>' . $v['name'] . ': <input type="text" name="' . $k . '" value="' . $v['value'] . '" /></p>';
                    break;
                case 'radio':
                    $yes_checked = $v['value'] == 'y' ? ' checked="checked"' : '';
                    $no_checked  = $v['value'] == 'n' ? ' checked="checked"' : '';
                    $data .= '<p>' . $v['name'] . ': <input type="radio" name="' . $k . '" value="y"' . $yes_checked . ' /> Yes <input type="radio" name="' . $k . '" value="n"' . $no_checked . ' /> No</p>';
                    break;
            }

        }

        $data .= '<hr /><p class="submit"><input type="submit" name="Submit" class="button-primary" value="Save Changes" /></p></form></div>';

        echo $data;
    }
}

// Create an instance of the bigboard class
$bb = new bigboard();

// Register actions
add_action('publish_post', array($bb, 'publish_post'));
add_action('publish_page', array($bb, 'publish_page'));
add_action('comment_post', array($bb, 'comment_post'));
add_action('wp_set_comment_status', array($bb, 'wp_set_comment_status'));
add_action('admin_menu', array($bb, 'options_menu'));

/* End of file bigboard.php */
/* Location: ./wp-content/plugins/bigboard/bigboard.php */