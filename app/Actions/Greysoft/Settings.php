<?php

namespace App\Actions\Greysoft;
 
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Http\File;

class Settings
{ 
    /**
     * Setting this to true will return  the configuration file as a json encoded string
     * @var boolean
     */
    public $json = false;

    /**
     * Get a particular setting group from a config file
     * @var string
     */
    public $group;

    /**
     * The configuration file to load
     * @var [type]
     */
    public $settings;

    /**
     * Set to true when running processes that don't require any output;
     * @var boolean
     */
    public $sync = false;

    /**
     * List of items to ignore
     * @var array
     */
    public $ignore = [
        'static_page_types',
        'permissions',
        'providers', 
        'aliases',
        'cipher',
        'faker_locale',
        'key',
    ];

    /**
     * List of config files to allow
     * @var array
     */
    public $allowed = [
        'settings', 
        'content', 
        'mail',
        'app',
    ];

    /**
     * List groups that can receive appends
     * @var array
     */
    public $expandable = [
        'settings.tracks', 
        'settings.genders', 
    ];

    /**
     * List groups that should be saved to .env
     * @var array
     */
    public $enviable = [
        'name', 
        'env', 
        'debug', 
        'url', 
        'key', 
        'asset_url', 
    ];

    /**
     * List the items to apply specific input types
     * @var array
     */
    public $form_control = [
        'checkbox' => ['keep_successful_queue_logs', 'add_title_to_logo', 'use_queue', 'debug'], 
        'image' => ['default_banner', 'default_avatar'], 
        'div' => ['variables'], 
    ];

    public function __construct()
    {
        $this->env_path = base_path('.env');
        $this->env_example_path = base_path('.env.example');
        $this->uploads_dir = 'public/media';
        $this->uploads_url = 'media';
    }

    /**
     * Sets the json variable to true
     * @return boolean true
     */
    public function json()
    {
        $this->json = true;
        return $this;
    }

    /**
     * Pass an array of options to modify the underlying variables
     * @param  array  $options
     * @return App\Actions\Greysoft\Settings
     */
    public function options(array $options)
    {
        if (isset($options['group'])) 
        {
            $this->group = $options['group'];
        }

        if (isset($options['settings'])) 
        {
            $this->settings = $options['settings'];
        }

        if (isset($options['allowed'])) 
        {
            $this->allowed = $options['allowed'];
        }

        if (isset($options['safe'])) 
        {
            $this->safe = $options['safe'];
        }

        return $this;
    }


    /**
     * Fetch the configuration settings
     * @param  string  $options This can be safely 
     * ignored when using the options() method
     * @return Illuminate\Support\Collection | json
     */
    public function get(string $settings=null)
    {
        $file = in_array(($settings ?: $this->settings), $this->allowed) 
            ? ($settings ?: $this->settings) 
            : 'settings';
        
        $this->settings = $file;
        $json = $this->json;

        $setting = $this->group ? "{$file}.{$this->group}" : $file;
        $loaded  = config($setting, []);
        $data    = collect(is_array($loaded) ? $loaded : [])
            ->filter(fn($config, $key) => !is_array($config) && !in_array($key, $this->ignore))
            ->mapWithKeys(function($value, $key) use ($file, $json) {
                return [
                    $key => [
                        'key'  => $key,
                        'title' => ucwords(($file==='app'?$file.' ':'') . \Str::of($key)->replace('_', ' ')->replace('-', ' ')),
                        'value' => $value,
                    ]
                ];  
        });

        return $this->json ? $data->values()->toJson() : $data;
    }

    /**
     * Fetch a settings group
     * @return Illuminate\Support\Collection
     */
    public function group()
    {
        $file = in_array($this->settings, $this->allowed) ? $this->settings : null;

        $loaded  = config($file, []);
        return collect(is_array($loaded) ? $loaded : [])
            ->filter(fn($config, $key) => is_array($config) && !in_array($key, $this->ignore))
            ->mapWithKeys(fn($conf, $key) => [$key => ucwords(\Str::of($key)->replace('_', ' ')->replace('-', ' '))]);
    }

    /**
     * Get the content of the config file.
     *
     * @return string
     */
    public function getConfigFile()
    {
        if (! file_exists(base_path("config/{$this->settings}.php"))) 
        {
            return false;
        }

        return file_get_contents(base_path("config/{$this->settings}.php"));
    }

    /**
     * Get the content of the .env file.
     *
     * @return string
     */
    public function getEnvFile()
    {
        if (! file_exists($this->env_path)) 
        {
            if (file_exists($this->env_example_path)) 
            {
                copy($this->env_example_path, $this->env_path);
            } 
            else 
            {
                touch($this->env_path);
            }
        }

        return file_get_contents($this->env_path);
    }

    /**
     * Get the content of the .env file.
     *
     * @return string
     */
    public function saveEnvFile($configs, $value = null, $type = null)
    {
        $env_file = $this->getEnvFile();
        $settings = $type ?? $this->settings;
        
        if (is_array($configs)) 
        {
            foreach ($configs as $config => $value) 
            {
                $env_key = $settings 
                    ? \Str::of($settings)->append('_'.$config)->upper() 
                    : \Str::of($config)->upper(); 

                $curr_val = env($env_key);

                if (stripos($env_file, $env_key->__toString()) !== FALSE)
                {
                    $env_file = \Str::of($env_file)->replace("$env_key=$curr_val", "$env_key=$value"); 
                    $env_file = \Str::of($env_file)->replace("$env_key = $curr_val", "$env_key = $value"); 
                }
                else
                {
                    $env_file = \Str::of($env_file)->append("$env_key=$value"); 
                }
            }
        }
        else
        {
            $config = $configs;

            $env_key = $settings 
                ? \Str::of($settings)->append('_'.$config)->upper() 
                : \Str::of($config)->upper(); 

            $curr_val = env($env_key);

            if (stripos($env_file, $env_key->__toString()) !== FALSE)
            {
                $env_file = \Str::of($env_file)->replace("$env_key=$curr_val", "$env_key=$value"); 
                $env_file = \Str::of($env_file)->replace("$env_key = $curr_val", "$env_key = $value"); 
            }
            else
            {
                $env_file = \Str::of($env_file)->append("$env_key=$value"); 
            }
        }

        try 
        {
            file_put_contents($this->env_path, $env_file);
            return $this->sync ? true : __('Configuring saved successfully');
        } 
        catch (Exception $e) 
        {
            return $this->sync ? false : __('Error saving configuration');
        }
    }

    /**
     * Save the form content to the config file.
     *
     * @param Request $request
     * @return string
     */
    public function saveConfigFile(array $configs, string $original = null)
    {
        $rawConfig = $this->getConfigFile();

        // Upload Images if any
        foreach ($this->form_control['image'] as $i => $img) 
        { 
            if (isset($configs[$img]) && $configs[$img]->isValid()) 
            {
                $curr_img = $this->group ? config($this->settings.'.'.$this->group.'.'.$img) : config($this->settings.'.'.$img);
                $curr_img = $this->uploads_dir . '/' . \Str::of($curr_img)->basename();
                !filter_var($curr_img, FILTER_VALIDATE_URL) && \Storage::delete($curr_img);
                $photo = new File($configs[$img]);
                $filename =  $img . '_' . rand() . '.' . $photo->extension();
                $new_file = \Storage::putFileAs($this->uploads_dir, $photo, $filename);
                $configs[$img] = $this->uploads_url . '/' . $filename;
            } 
        }

        foreach (array_reverse($configs) as $config => $value) 
        {
            $conf_val = $this->group ? config($this->settings.'.'.$this->group.'.'.$config) : config($this->settings.'.'.$config);
            $curr_val = \Str::isBool($conf_val) || in_array($config, $this->form_control['checkbox']) 
                ? (in_array($conf_val, [1, '1', true, 'true']) ? 'true' : 'false') 
                : "'$conf_val'";

            $value    = \Str::of($value)->replace("'", "\'")->trim();
            $new_val  = \Str::isBool($value) || in_array($config, $this->form_control['checkbox']) 
                ? (in_array($value->__toString(), [1, '1', true, 'true']) ? 'true' : 'false') 
                : "'$value'";

            if (in_array($config, $this->enviable)) 
            {
                $sync = $this->sync;
                $this->sync = true;
                $this->saveEnvFile($configs, $value);
                $this->sync = $sync;
            }
            elseif (stripos($rawConfig, $config) !== FALSE) 
            {
                $rawConfig = \Str::of($rawConfig)->replace("'$config' => $curr_val", "'$config' => $new_val");
            }
            elseif ($this->group && $config && $value->isNotEmpty() && stripos($rawConfig, $this->group) !== FALSE) 
            {
                $lastGroupKey = collect(config($this->settings.'.'.$this->group))->keys()->last();
                $lastGroupVal = collect(config($this->settings.'.'.$this->group))->values()->last();
                $lastGroupVal = \Str::of($lastGroupVal)->replace("'", "\'")->trim();
                $raw_val      = \Str::isBool($lastGroupVal) || in_array($config, $this->form_control['checkbox']) 
                    ? (in_array($lastGroupVal->__toString(), [1, '1', true, 'true']) ? 'true' : 'false') 
                    : "'$lastGroupVal'";

                $rawGroup = "'$lastGroupKey' => $raw_val,\n";
                $rawGroup .= "        '$config' => $new_val,\n";

                $rawConfig = \Str::of($rawConfig)->replace("'$lastGroupKey' => $raw_val,", "$rawGroup"); 
            }
        }

        $verify = collect(json_decode($original))->mapWithKeys(fn($e) => [$e->key => $e->value])->keys()->diff(collect($configs)->keys());

        foreach ($verify as $e) 
        {
            $curr_val  = $this->group ? config($this->settings.'.'.$this->group.'.'.$e) : config($this->settings.'.'.$e);
            $rawConfig = \Str::of($rawConfig)->replace("'$e' => '$curr_val',", null);  
        } 

        $rawConfig = preg_replace("/\n+/m", "\n", $rawConfig);

        try 
        {
            file_put_contents(base_path("config/{$this->settings}.php"), $rawConfig);
            return $this->sync ? true : __('Configuring saved successfully');
        } 
        catch (Exception $e) 
        {
            return $this->sync ? false : __('Error saving configuration');
        }
    }

    public function loadModel($model='')
    {
        $setModel = \Str::of($model)->studly();
        $ModelClass = "\App\Models\\".$setModel;
        return new $ModelClass();
    }

    public function classLoader($class='', $namespace=false)
    {
        $setClass = \Str::of($class)->studly();
        $Class = $namespace ? $namespace."\\".$setModel : "\\".$class;
        return (new $Class());
    }
}