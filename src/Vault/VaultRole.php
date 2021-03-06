<?php namespace Rappasoft\Vault;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Class VaultRole
 * @package Rappasoft\Vault
 */
class VaultRole extends Model {
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table;

	public function __construct()
	{
		$this->table = Config::get('vault.roles_table');
	}

	/**
	 * Many-to-Many relations with Users.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function users()
	{
		return $this->belongsToMany(Config::get('auth.model'), Config::get('vault.assigned_roles_table'), 'role_id', 'user_id');
	}

	/**
	 * Many-to-Many relations with Permission.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function permissions()
	{
		return $this->belongsToMany(Config::get('vault.permission'), Config::get('vault.permission_role_table'), 'role_id', 'permission_id');
	}

	/*
	 * Get the edit permission button
	 */
	public function getEditButtonAttribute() {
		return '<a href="'.route('access.roles.edit', $this->id).'" class="btn btn-xs btn-primary"><i class="fa fa-pencil" data-toggle="tooltip" data-placement="top" title="Edit"></i></a>';
	}

	/*
	 * Get the delete permission button
	 */
	public function getDeleteButtonAttribute() {
		if ($this->id != 1) //Cant delete master admin role
			return '<a href="'.route('access.roles.destroy', $this->id).'" class="btn btn-xs btn-danger" data-method="delete"><i class="fa fa-times" data-toggle="tooltip" data-placement="top" title="Delete"></i></a>';
		return '';
	}

	/*
	 * Get the action buttons for the role
	 */
	public function getActionButtonsAttribute() {
		return $this->getEditButtonAttribute().' '.$this->getDeleteButtonAttribute();
	}

	/**
	 * Before delete all constrained foreign relations
	 *
	 * @return bool
	 */
	public function beforeDelete()
	{
		DB::table(Config::get('vault.assigned_roles_table'))->where('role_id', $this->id)->delete();
		DB::table(Config::get('vault.permission_role_table'))->where('role_id', $this->id)->delete();
	}


	/**
	 * Save the inputted permissions.
	 *
	 * @param mixed $inputPermissions
	 *
	 * @return void
	 */
	public function savePermissions($inputPermissions)
	{
		if (!empty($inputPermissions)) {
			$this->permissions()->sync($inputPermissions);
		} else {
			$this->permissions()->detach();
		}
	}

	/**
	 * Attach permission to current role.
	 *
	 * @param object|array $permission
	 *
	 * @return void
	 */
	public function attachPermission($permission)
	{
		if (is_object($permission)) {
			$permission = $permission->getKey();
		}

		if (is_array($permission)) {
			$permission = $permission['id'];
		}

		$this->permissions()->attach($permission);
	}

	/**
	 * Detach permission form current role.
	 *
	 * @param object|array $permission
	 *
	 * @return void
	 */
	public function detachPermission($permission)
	{
		if (is_object($permission))
			$permission = $permission->getKey();

		if (is_array($permission))
			$permission = $permission['id'];

		$this->permissions()->detach($permission);
	}

	/**
	 * Attach multiple permissions to current role.
	 *
	 * @param mixed $permissions
	 *
	 * @return void
	 */
	public function attachPermissions($permissions)
	{
		foreach ($permissions as $permission) {
			$this->attachPermission($permission);
		}
	}

	/**
	 * Detach multiple permissions from current role
	 *
	 * @param mixed $permissions
	 *
	 * @return void
	 */
	public function detachPermissions($permissions)
	{
		foreach ($permissions as $permission) {
			$this->detachPermission($permission);
		}
	}
}
