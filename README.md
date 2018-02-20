# Video Embedder plugin for Craft CMS 3.x

Craft plugin to generate an embed URL from a YouTube or Vimeo URL. This plugin will be 100% free.

Ported over from [Viget's](https://viget.com) [Video Embed plugin for Craft 2.x](https://github.com/vigetlabs/craft-videoembed).

## Requirements

This plugin requires Craft CMS 3.0.0-RC1 or later.

## Installation

To install Video Embedder, follow these steps:

1. Install with Composer via `composer require mikestecker/craft-videoembedder` from your project directory
2. Install plugin in the Craft Control Panel under Settings > Plugins

Video Embedder works on Craft 3.x.

## Video Embedder Overview

Video Embedder will take your YouTube or Vimeo URL's and convert the URL into an embed-friendly URL for use inside an iframe tag. Video Embedder also has a variable which will take the same URL and get the thumbnail image URL.

## Using Video Embedder

Pass a YouTube or Vimeo URL to the `getEmbedUrl` variable and an embed URL will be returned.

```
{{ craft.videoEmbedder.getEmbedUrl('https://www.youtube.com/watch?v=6xWpo5Dn254') }}
```

**Example:**

```
<iframe src="{{ craft.videoEmbedder.getEmbedUrl('https://www.youtube.com/watch?v=6xWpo5Dn254') }}"></iframe>
```

**Output:**

```
<iframe src="//www.youtube.com/embed/6xWpo5Dn254"></iframe>
```

___

***New in 1.0.5:***

 `getEmbedUrl` will now accept optional parameters to YouTube and Vimeo URL's such as `autoplay`, `rel`, etc:

```
{{ craft.videoEmbedder.getEmbedUrl('https://www.youtube.com/watch?v=6xWpo5Dn254', {autoplay: 1, rel: 0, theme: 'dark'}) }}
```

___

Video Embedder will also pull the largest thumbnail URL from YouTube or Vimeo using the `getVideoThumbnail` variable.

```
{{ craft.videoEmbedder.getVideoThumbnail('https://www.youtube.com/watch?v=6xWpo5Dn254') }}
```

**Output:**

```
//img.youtube.com/vi/6xWpo5Dn254/0.jpg
```

___

***New in 1.0.5:*** 

Video Embedder will now generate the iframe code. Simple use the `embed` variable with your URL. You can also pass in optional parameters to YouTube and Vimeo URL's such as `autoplay`, `rel`, etc.

Basic example:
```
{{ craft.videoEmbedder.embed('https://www.youtube.com/watch?v=6xWpo5Dn254') }}
```

With parameters:
```
{{ craft.videoEmbedder.embed('https://www.youtube.com/watch?v=6xWpo5Dn254', {autoplay: 1, rel: 0, theme: 'dark'}) }}
```

These parameters will simply output at the end of the embed URL string and have only been tested with YouTube and Vimeo. Check with each provider for which parameters are supported.


## Video Embedder Roadmap

Some things to do, and ideas for potential features:

- [x] Add in the ability to actually generate the iframe HTML
- [ ] Add new Video URL field type that only allows for supported video URL's
- [ ] Add support for more video providers (partially added with 1.0.5 by switching to using the [Embed](https://github.com/oscarotero/Embed) library)
- [ ] Add more thumbnail size options
- [ ] Testing

This is my first plugin and I'm not very experienced with plugin development. Feel free to fork away, add whatever you'd like to see and send me a pull request.

Brought to you by [Mike Stecker](http://github.com/mikestecker)
