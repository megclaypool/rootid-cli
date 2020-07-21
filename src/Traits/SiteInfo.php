<?php

namespace RootidCLI\Traits;

trait SiteInfo
{

  function getSite($site_name = '')
  {
    $out = shell_exec("terminus site:info {$site_name} --format=json");
    $out = json_decode($out);
    return $out;
  }

  /**
   * Returns an array of site slugs that the user has access to.
   */
  function getSiteList()
  {
    $sites = shell_exec('terminus site:list --format=json');
    $sites = json_decode($sites);
    $output = [];

    foreach ($sites as $key => $site) {
      $output[] = $site->name;
    }

    return $output;
  }

  function getLocalSiteRoot($site)
  {
    $path = 'http://' . $site->name . '.' . \Robo\Robo::config()->get('options.local_domain');
    // if(\Robo\Robo::config()->get('web_docroot')) {
    //     $path .= '/web';
    // }

    return $path;
  }

  function getSiteEnvs($site)
  {
    $temp = shell_exec("terminus env:list --format list --fields ID {$site->name}");
    $out = array_filter(preg_split("/(\r\n|\r|\n)/", $temp));
    return $out;
  }
}
