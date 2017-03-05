<?php

namespace Yarob\LaravelExpirable;

use Yarob\LaravelExpirable\Services\ExpiryScope;

/**
 * Class HasSettings
 *
 * @package Yarob\HasSettings
 */
trait Expirable
{
	/**
	 * Indicates if the model is currently force deleting expired Models.
	 *
	 * @var bool
	 */
	protected $forceExpiring = false;

	/**
	 * Boot the soft deleting trait for a model.
	 *
	 * @return void
	 */
	public static function bootExpiry()
	{
		static::addGlobalScope(new ExpiryScope);
	}

	/**
	 * Force a hard delete on a soft deleted model.
	 *
	 * @return bool|null
	 */
	public function forceExpiry()
	{
		$this->forceExpiring = true;

		$deleted = $this->delete();

		$this->forceExpiring = false;

		return $deleted;
	}

	/**
	 * Perform the actual delete query on this model instance.
	 *
	 * @return mixed
	 */
	protected function performExpiryOnModel()
	{
		if ($this->forceExpiring) {
			return $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey())->forceDelete();
		}

		return $this->runSoftExpiry();
	}

	/**
	 * Perform the actual delete query on this model instance.
	 *
	 * @return void
	 */
	protected function runSoftExpiry()
	{
		$query = $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey());

		$this->{$this->getExpiredAtColumn()} = $time = $this->freshTimestamp();

		$query->update([$this->getExpiredAtColumn() => $this->fromDateTime($time)]);
	}

	/**
	 * Restore a soft-expired model instance.
	 *
	 * @return bool|null
	 */
	public function restoreSoftExpired()
	{
		// If the restoring event does not return false, we will proceed with this
		// restore operation. Otherwise, we bail out so the developer will stop
		// the restore totally. We will clear the deleted timestamp and save.
		if ($this->fireModelEvent('restoringSoftExpired') === false) {
			return false;
		}

		$this->{$this->getExpiredAtColumn()} = null;

		// Once we have saved the model, we will fire the "restored" event so this
		// developer will do anything they need to after a restore operation is
		// totally finished. Then we will return the result of the save call.
		$this->exists = true;

		$result = $this->save();

		$this->fireModelEvent('restoredSoftExpired', false);

		return $result;
	}

	/**
	 * Determine if the model instance has been soft-expired.
	 *
	 * @return bool
	 */
	public function isSoftExpired()
	{
		return ! is_null($this->{$this->getExpiredAtColumn()});
	}

	/**
	 * Register a restoring model event with the dispatcher.
	 *
	 * @param  \Closure|string  $callback
	 * @return void
	 */
	public static function expiring($callback)
	{
		static::registerModelEvent('expiring', $callback);
	}

	/**
	 * Register a restored model event with the dispatcher.
	 *
	 * @param  \Closure|string  $callback
	 * @return void
	 */
	public static function expired($callback)
	{
		static::registerModelEvent('expired', $callback);
	}

	/**
	 * Determine if the model is currently force deleting.
	 *
	 * @return bool
	 */
	public function isForceExpiring()
	{
		return $this->forceExpiring;
	}

	/**
	 * Get the name of the "deleted at" column.
	 *
	 * @return string
	 */
	public function getExpiredAtColumn()
	{
		return defined('static::EXPIRED_AT') ? static::EXPIRED_AT : 'expired_at';
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
}
