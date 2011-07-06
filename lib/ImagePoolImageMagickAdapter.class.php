<?php
/**
 * Custom sfThumbnail adapter for sfImagePoolPlugin
 *
 * @package symfony
 * @subpackage sfImagePoolPlugin
 * @author Ben Lancaster
 */
class ImagePoolImageMagickAdapter extends sfImageMagickAdapter
{
  public function save($thumbnail, $thumbDest, $targetMime = null)
  {
    $command = '';

    $width  = $this->sourceWidth;
    $height = $this->sourceHeight;
    $x = $y = 0;
    switch (@$this->options['method']) 
    {
      case "shave_all":
        $proportion['source'] = $width / $height;
        $proportion['thumb'] = $thumbnail->getThumbWidth() / $thumbnail->getThumbHeight();
        
        if ($proportion['source'] > 1 && $proportion['thumb'] < 1)
        {
          $x = ($width - $height * $proportion['thumb']) / 2;
        }
        else
        {
          if ($proportion['source'] > $proportion['thumb'])
          {
            $x = ($width - $height * $proportion['thumb']) / 2;
          }
          else
          {
            $y = ($height - $width / $proportion['thumb']) / 2;
          }
        }

        $command = sprintf(" -shave %dx%d", $x, $y);
        break;

      case "shave_bottom":
        if ($width > $height)
        {
          $x = ceil(($width - $height) / 2 );
          $width = $height;
        }
        elseif ($height > $width)
        {
          $y = 0;
          $height = $width;
        }

        if (is_null($thumbDest))
        {
          $command = sprintf(
            " -crop %dx%d+%d+%d %s '-' | %s",
            $width, $height,
            $x, $y,
            escapeshellarg($this->image),
            $this->magickCommands['convert']
          );

          $this->image = '-';
        }
        else
        {
          $command = sprintf(
            " -crop %dx%d+%d+%d %s %s && %s",
            $width, $height,
            $x, $y,
            escapeshellarg($this->image), escapeshellarg($thumbDest),
            $this->magickCommands['convert']
          );

          $this->image = $thumbDest;
        }

        break;
      case 'custom':
      	$coords = $this->options['coords'];
      	if (empty($coords)) break;
      	
      	$x = $coords['x1'];
      	$y = $coords['y1'];
      	$width = $coords['x2'] - $coords['x1'];
      	$height = $coords['y2'] - $coords['y1'];
      	
        if (is_null($thumbDest))
        {
          $command = sprintf(
            " -crop %dx%d+%d+%d %s '-' | %s",
            $width, $height,
            $x, $y,
            escapeshellarg($this->image),
            $this->magickCommands['convert']
          );

          $this->image = '-';
        }
        else
        {
          $command = sprintf(
            " -crop %dx%d+%d+%d %s %s && %s",
            $width, $height,
            $x, $y,
            escapeshellarg($this->image), escapeshellarg($thumbDest),
            $this->magickCommands['convert']
          );

          $this->image = $thumbDest;
        }
      	break;
    } // end switch

    $command .= ' -thumbnail ';
    $command .= $thumbnail->getThumbWidth().'x'.$thumbnail->getThumbHeight();

    // absolute sizing
    if (!$this->scale)
    {
      $command .= '!';
    }

    if(is_null($targetMime) || $targetMime == 'image/jpeg')
    {
      if ($this->quality )
      {
        $command .= sprintf(' -quality %u%% ',$this->quality);
      }
      if(isset($this->options['sharpen']))
      {
        $command .= ' -unsharp 1.5x1.2+0.7+0.10 ';
      }
    }

    // extract images such as pages from a pdf doc
    $extract = '';
    if (isset($this->options['extract']) && is_int($this->options['extract']))
    {
      if ($this->options['extract'] > 0)
      {
        $this->options['extract']--;
      }
      $extract = '['.escapeshellarg($this->options['extract']).'] ';
    }

    $output = (is_null($thumbDest))?'-':$thumbDest;
    $output = (($mime = array_search($targetMime, $this->mimeMap))?$mime.':':'').$output;

    $cmd = $this->magickCommands['convert'].' '.$command.' '.escapeshellarg($this->image).$extract.' '.escapeshellarg($output);
    
    (is_null($thumbDest))?passthru($cmd):exec($cmd);
  }
}