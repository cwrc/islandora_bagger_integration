<?php

namespace Drupal\islandora_bagger_integration\Utils;

use Drupal\Core\Config\ImmutableConfig;
use Symfony\Component\Yaml\Yaml;

/**
 * Utility methods.
 */
class IslandoraBaggerUtils {

  /**
   * The Islandora Bagger Integration settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  public function __construct() {
    $config = \Drupal::config('islandora_bagger_integration.settings');
    $this->config = $config;
  }

  /**
   * Gets the path to the Islandora Bagger config file.
   *
   * @return string|bool
   *   The absolute path to the file on the Drupal server, or FALSE if no file is found.
   */
  public function getConfigFilePath(): string|bool {

    $islandora_bagger_config_file_path_from_context = NULL;
    if (\Drupal::moduleHandler()->moduleExists('context')) {
      $context_manager = \Drupal::service('context.manager');
      // If there are multiple contexts that provide a path to a config file, it's OK to use the last one.
      foreach ($context_manager->getActiveReactions('islandora_bagger_integration_config_file_paths') as $reaction) {
        // @phpstan-ignore-line
        $islandora_bagger_config_file_path_from_context = $reaction->execute();
      }
    }

    if (isset($islandora_bagger_config_file_path_from_context) && strlen($islandora_bagger_config_file_path_from_context)) {
      $islandora_bagger_config_file_path = $islandora_bagger_config_file_path_from_context;
    }
    else {
      $islandora_bagger_config_file_path = $this->config->get('islandora_bagger_default_config_file_path');
    }

    if (file_exists($islandora_bagger_config_file_path)) {
      return $islandora_bagger_config_file_path;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Checks to see if the Islandora Bagger config file exists and is readable.
   *
   * @param string $path
   *   Path to the file to test.
   *
   * @return bool
   *   TRUE if it is, FALSE if not.
   */
  public function configFileIsReadable(string $path = NULL): bool {
    if (is_null($path)) {
      $path = $this->getConfigFilePath();
    }

    if (is_readable($path)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Gets the configuration file values.
   *
   * @param string $path
   *   Path to the config file.
   *
   * @return array<string, mixed>|bool
   *   The configuration values, or FALSE if there was a problem.
   */
  public function getIslandoraBaggerConfig(string $path): array|bool {
    if (!$this->configFileIsReadable($path)) {
      return FALSE;
    }

    // Use a static cache to avoid reading the config file multiple times.
    static $settings;
    if (!isset($settings[$path])) {
      $settings = Yaml::parseFile($path);
    }
    return $settings;
  }


  /**
   * Incorporates bag-info tags from the Context "IslandoraBaggerConfigurationOptionsReaction" Reaction into the existing YAML config data.
   *
   * New tags are added, and existing tags are overwritten with new values.
   *
   * @param array<string, mixed> $existing_config
   *    The YAML from the config file template.
   * @param string $bag_info_tags_from_context
   *    The pipe-separated bag-info tags from the Context configuration.
   *
   * @return array<string, mixed>
   *    The modified YAML configuration data as an associative array.
   */
  public function addBagInfoTags(array $existing_config, string $bag_info_tags_from_context): array {
    $bag_info_tags_from_context = explode('|', $bag_info_tags_from_context);
    foreach ($bag_info_tags_from_context as $tag_from_context) {
      list($context_tag_key, $context_tag_value) = explode(':', $tag_from_context, 2);
      $context_tag_key = trim($context_tag_key);
      $context_tag_value = trim($context_tag_value);
      // If the tag exists, replace it with the corresponding value from the Context;
      // if it doesn't, add it using the value from the Context.
     $existing_config['bag-info'][$context_tag_key] = $context_tag_value;
  }

    return $existing_config;
  }

  /**
   * Incorporates list values from the Context "IslandoraBaggerConfigurationOptionsReaction" Reaction into the existing YAML config data.
   *
   * New tags are added, and existing tags are overwritten with new values.
   *
   * @param array<string, mixed> $existing_config
   *    The YAML from the config file template.
   * @param string $key
   *    The YAML key to update.
   * @param string $list_from_context
   *    The pip-separate list of values from the Context configuration.
   *
   * @return array<string, mixed>
   *    The modified YAML configuration data as an associative array.
   */
  public function addListConfigOptions(array $existing_config, string $key, string $list_from_context): array {
    $list_from_context = explode('|', $list_from_context);
    foreach ($list_from_context as &$member) {
      $member = trim($member);
      // If the key exists, replace it with the corresponding list value from the Context;
      // if it doesn't, add it using the list value from the Context.
      $existing_config[$key] = $list_from_context;
     }

    return $existing_config;
  }
}

