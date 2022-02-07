<?php
/**
 * Video Embedder plugin for Craft CMS 3.x
 *
 * Craft plugin to generate an embed URL from a YouTube or Vimeo URL.
 *
 * @link      http://github.com/mikestecker
 * @copyright Copyright (c) 2017 Mike Stecker
 */

namespace mikestecker\videoembedder\services;

use Embed\Exceptions\InvalidUrlException;
use mikestecker\videoembedder\VideoEmbedder;

use Craft;
use craft\base\Component;

use DOMDocument;
use Embed\Embed;

/**
 * @author    Mike Stecker
 * @package   VideoEmbedder
 * @since     1.0.0
 */
class VideoEmbedderService extends Component
{


    /**
     * Tap the Embed library
     * @param string $url
     * @return boolean
     */
    public function getInfo($url)
    {
		// use oembed if vimeo
		if ($this->isVimeo($url) && !$this->isOembed($url)) {
			$url = 'https://vimeo.com/api/oembed.json?url='.$url;
		}
		
		try {
			$response = Embed::create($url, [
				'choose_bigger_image' => true,
				'parameters' => [],
			]);
		} 
		catch (Exception $e) 
		{
			$response = null;
		}
		
		if ($this->isVimeo($url) && $this->isOembed($url)) {
			$data = json_decode($response->response->getContent());
			$data->aspectRatio = $data->width / $data->height;
			$data->code = $data->html;
			return $data;
		}

		return $response;
    }


    /**
     * Determine whether the url is a youtube or vimeo url
     * @param string $url
     * @return boolean
     */
    public function isVideo($url)
    {
        return ($this->getInfo($url)->type == 'video');
    }

    /**
     * Is the url a youtube url
     * @param string $url
     * @return boolean
     */
    public function isYouTube($url)
    {
        return (strpos($url, 'youtube.com/') !== false || strpos($url, 'youtu.be/') !== false);
    }

    /**
     * Is the url a vimeo url
     * @param string $url
     * @return boolean
     */
    public function isVimeo($url)
    {
        return strpos($url, 'vimeo.com/') !== FALSE;
    }

    /**
     * Is the url a wistia url
     * @param string $url
     * @return boolean
     */
    public function isWistia($url)
    {
        return strpos($url, 'wistia.com/') !== FALSE;
    }

    /**
     * Is the url a Viddler url
     * @param string $url
     * @return boolean
     */
    public function isViddler($url)
    {
        return strpos($url, 'viddler.com/') !== FALSE;
	}
	
	/**
     * Is the url using oEmbed format
     * @param string $url
     * @return boolean
     */
    public function isOembed($url)
    {
        return strpos($url, 'oembed.json?') !== FALSE;
	}

    /**
     * Parse the YouTube URL, return the video ID
     * @param string $url
     * @return string
     */
    public function getYouTubeId($url)
    {
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
        return $match[1];
    }

    /**
     * Parse the Vimeo URL, return the video ID
     * @param string $url
     * @return string
     */
    public function getVimeoId($url)
    {
        preg_match('%^https?:\/\/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)(?:[?]?.*)$%im', $url, $matches);
        return $matches[3];
    }

    // For youtube videos, eliminate the getInfo calls in order to prevent HTTP/429s coming from youtube and crashing the site.
    public function embedYoutubeVideo($url, $params) {
        try {
            $videoId = $this->getYouTubeId($url);

            // check if theree are any parameters passed along
            if (!empty($params)) {

                // looks like there are, now let's only do this for YouTube and Vimeo
                // if($this->getInfo($url)->type == 'video' && ($this->isYouTube($url) || $this->isVimeo($url)))
                if($this->isYouTube($url) || $this->isVimeo($url))
                {
                    $dom = new DOMDocument;
                    $internalErrors = libxml_use_internal_errors(true);

                    $frame = $dom->createElement("iframe");
                    $frame->setAttribute("src", "https://www.youtube.com/embed/".$videoId);
                    $frame->setAttribute("frameborder", "0");
                    $frame->setAttribute("allowTransparency", "true");
                    $frame->setAttribute("allowFullscreen", "allowFullscreen");

                    //get the src from iframe
                    $src = $frame->getAttribute('src');

                    // check if url has any other parameters to properly add the parameter
                    if (strpos($src,'?') !== false) {
                        $src .= "&";
                    } else {
                        $src .= "?";
                    }

                    $parameters = '';
                    foreach ($params as $k=>$v) {
                        if ($parameters !== null) {
                            $parameters .= '&';
                        }
                        $parameters .= "{$k}={$v}";
                    }

                    $src .= $parameters;

                    // set the new src with all the parameters
                    $frame->setAttribute('src', $src);

                    // replace old iframe html with new one
                    return htmlspecialchars_decode($dom->saveXML($frame, LIBXML_NOEMPTYTAG));
                }
                else
                {
                    if (!empty($code)) {
                        // Not YouTube or Vimeo, just output the code
                        return $code;
                    }
                    else
                    {
                        return '';
                    }
                }
            }
            else
            {
                if (!empty($code)) {
                    // No parameters passed, just output the code
                    return $code;
                }
                else
                {
                    return '';
                }
            }
        } catch (InvalidUrlException $e)
        {
            // If the URL is invalid (because it's 404ing out or whatever) just return an empty string.
            return '';
        }
    }

    // This is a little bit redundant based on the original plugin, but this should ensure that Vimeo videos still work.
    public function embedVimeoVideo($url, $params) {
        try {
            $code = $this->getInfo($url)->code;

            // check if theree are any parameters passed along
            if (!empty($params)) {

                // looks like there are, now let's only do this for YouTube and Vimeo
                if($this->getInfo($url)->type == 'video' && ($this->isYouTube($url) || $this->isVimeo($url)))
                {

                    // for videos add autoplay check if the embed gets the code

                    // create an easy html parser to verify the iframe and add autoplay for video types
                    $dom = new DOMDocument;

                    // set error level so that the html parser doesn't raise exceptions on warnings
                    $internalErrors = libxml_use_internal_errors(true);

                    $dom->loadHTML($code);

                    // Restore error level
                    libxml_use_internal_errors($internalErrors);

                    // get the iframe
                    $frame = $dom->getElementsByTagName('iframe')->item(0);

                    //get the src from iframe
                    $src = $frame->getAttribute('src');


                    // check if url has any other parameters to properly add the parameter
                    if (strpos($src,'?') !== false) {
                        $src .= "&";
                    } else {
                        $src .= "?";
                    }

                    $parameters = '';
                    foreach ($params as $k=>$v) {
                        if ($parameters !== null) {
                            $parameters .= '&';
                        }
                        $parameters .= "{$k}={$v}";
                    }

                    $src .= $parameters;

                    // set the new src with all the parameters
                    $frame->setAttribute('src', $src);

                    // replace old iframe html with new one
                    return htmlspecialchars_decode($dom->saveHTML($frame));
                }
                else
                {
                    if (!empty($code)) {
                        // Not YouTube or Vimeo, just output the code
                        return $code;
                    }
                    else
                    {
                        return '';
                    }
                }
            }
            else
            {
                if (!empty($code)) {
                    // No parameters passed, just output the code
                    return $code;
                }
                else
                {
                    return '';
                }
            }
        } catch (InvalidUrlException $e)
        {
            // If the URL is invalid (because it's 404ing out or whatever) just return an empty string.
            return '';
        }

    }

    /**
     * Take a url and return the embed code
     *
     * @param string $url
     * @return string
     */
    public function embed( $url, $params = [] ) : string
    {
        if($this->isYouTube($url)) {
            return $this->embedYoutubeVideo($url, $params);
        }

        return $this->embedVimeoVideo($url, $params);
    }

    /**
     * Take a url and return the embed url
     *
     * @param string $url
     * @return string
     */
    public function getEmbedUrl($url, $params = [] )
    {

        try {

            // looks like there are, now let's only do this for YouTube and Vimeo
            if($this->getInfo($url)->type == 'video' && ($this->isYouTube($url) || $this->isVimeo($url)))
            {
                $parameters = '';
    
                // check if theree are any parameters passed along
                if (!empty($params)) {
                    
                    $parameters .= '?';
                    $i = 0;
                    foreach ($params as $k=>$v) {
                        if (($parameters !== null) && ($i !== 0)) {
                            $parameters .= '&';
                        }
                        $parameters .= "{$k}={$v}";
                        $i++;
                    }
                }
                
                if ($this->isYouTube($url)) {
                    $id = $this->getYouTubeId($url);
        
                    $embedUrl = '//www.youtube.com/embed/' . $id . $parameters;
                    return $embedUrl;
                } else if ($this->isVimeo($url)) {
                    $id = $this->getVimeoId($url);
        
                    $embedUrl = '//player.vimeo.com/video/' . $id . $parameters;
                    return $embedUrl;
                }
            }
            else
            {
                // return empty string
                return '';
            }

        } catch (InvalidUrlException $e)
        {
            // If the URL is invalid (because it's 404ing out or whatever) just return an empty string.
            return '';
        }

    }



    /**
     * Take a url and returns only the video ID
     *
     * @param string $url
     * @return string
     */
    public function getVideoId($url)
    {
        // looks like there are, now let's only do this for YouTube and Vimeo
        if($this->getInfo($url)->type == 'video' && ($this->isYouTube($url) || $this->isVimeo($url)))
        {
            if ($this->isYouTube($url))
            {
                return $this->getYouTubeId($url);
            }
            else if ($this->isVimeo($url))
            {
                return $this->getVimeoId($url);
            }
        }
        else
        {
            // return empty string
            return '';
        }
    }


    /**
     * Retrieves the thumbnail from a youtube or vimeo video
     * @param - $url
     * @return - string
     * 
    **/
    public function getVideoThumbnail($url) {
        // check for vimeo, I don't like the way Embed returns the Vimeo thumbnail
        if($this->getInfo($url)->type == 'video' && $this->isVimeo($url))
        {
            $id = $this->getVimeoId($url);
            
            $data = file_get_contents("http://vimeo.com/api/v2/video/$id.json");
            $data = json_decode($data);
            
            $image = $this->cleanUrl($data[0]->thumbnail_large);
            
            return $image;
        }
        else
        {
            // not vimeo, use Embed
            $image = $this->cleanUrl($this->getInfo($url)->image);
            
            // Check if anything exists
            if (!empty($image)) {
                return $image;
            }
            else
            {
                return '';
            }
        }
    }

    private function cleanUrl($url) {
        $stripped = preg_replace( '/^https?:/', '', $url );
        return $stripped;
    }
    
}
