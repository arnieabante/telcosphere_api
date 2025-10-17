<?php

namespace App\Permissions;

use App\Models\User;

final class Abilties
{
    public const CreateItem = 'item:create';
    public const UpdateItem = 'item:update';
    public const ReplaceItem = 'item:replace';
    public const DeleteItem = 'item:delete';
    public const ViewItem = 'item:view';

    public const CreateUser = 'user:create';
    public const UpdateUser = 'user:update';
    public const ReplaceUser = 'user:replace';
    public const DeleteUser = 'user:delete';

    public static function getAbilities(User $user) {
        $roles = explode(',', $user->role);

        if (in_array('admin', $roles)) {
            return [
                self::CreateItem,
                self::UpdateItem,
                self::ReplaceItem,
                self::DeleteItem,
                self::CreateUser,
                self::UpdateUser,
                self::ReplaceUser,
                self::DeleteUser
            ];
        } else if (in_array('manager', $roles)) {
            return [
                self::CreateItem,
                self::UpdateItem,
                self::ReplaceItem,
                self::DeleteItem
            ];
        } else { // guest user
            return [
                self::ViewItem
            ];
        }
    }
}