<?php

namespace Yarob\LaravelExpirable;

use Carbon\Carbon;
use Yarob\LaravelExpirable\Services\ExpiryScope;

/**
 * Class HasSettings
 *
 * @package Yarob\HasSettings
 */
trait Expirable
{

	/**
	 * Boot the soft deleting trait for a model.
	 *
	 * @return void
	 */
	public static function bootExpirable()
	{
		static::addGlobalScope(new ExpiryScope);
	}

	/**
	 * Revive an expired model instance.
	 *
	 * @param null $revivalTime
	 *
	 * @return bool|null
	 */
	public function reviveExpired($revivalTime = null )
	{
		if($this->expire_at < Carbon::now())
		{
			if ($this->fireModelEvent('revivingExpiry') === false) {
				return false;
			}

			$revivalTime = $revivalTime ? $revivalTime : $this->getConfiguration()['revival_time'];

			if(!empty($revivalTime))
			{
				$this->{$this->getExpiredAtColumn()} = Carbon::now()->addSeconds($revivalTime);

				$result = $this->save();

				$this->fireModelEvent('revivedExpiry', false);

				return $result;
			}
		}
		return false;
	}

	/**
	 * return number of seconds left in model's life
	 *
	 * @return int/bool
	 */
	public function timeToLive()
	{
		if(is_object($this->expire_at))
		{
			return -1 * $this->expire_at->diffInSeconds(Carbon::now(), false);
		}
		return false;
	}

	/**
	 * check if model is expired
	 *
	 * @return bool
	 */
	public function hasExpired()
	{
		if(is_object($this->expire_at))
		{
			return ( $this->expire_at < Carbon::now() );
		}
		return false;
	}

	/**
	 * Register a expiring model event with the dispatcher.
	 *
	 * @param  \Closure|string  $callback
	 * @return void
	 */
	public static function expiring($callback)
	{
		static::registerModelEvent('expiring', $callback);
	}

	/**
	 * Register a expired model event with the dispatcher.
	 *
	 * @param  \Closure|string  $callback
	 * @return void
	 */
	public static function expired($callback)
	{
		static::registerModelEvent('expired', $callback);
	}


	/**
	 * Get the name of the "deleted at" column.
	 *
	 * @return string
	 */
	public function getExpiredAtColumn()
	{
		return defined('static::EXPIRE_AT') ? static::EXPIRE_AT : 'expire_at';
	}

	/**
	 * Get the fully qualified "deleted at" column.
	 *
	 * @return string
	 */
	public function getQualifiedExpiredAtColumn()
	{
		return $this->getTable().'.'.$this->getExpiredAtColumn();
	}

	/**
	 * Get Model settings configuration for the current model,
	 *
	 * @return array
	 */
	private function getConfiguration()
	{
		static $defaultConfig = null;

		if ($defaultConfig === null) {
			$defaultConfig = app('config')->get('expirable');
		}

		return $defaultConfig[class_basename($this)];
	}
}
