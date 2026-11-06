<?php
namespace app\services;
use app\models\User;
use DomainException;
class AccountPolicy
{
    public static function role(User $actor,?User $target,?string $requested): string
    {
        if(!$actor->isAdmin())throw new DomainException('Administrator huquqi kerak.');
        if(!$target && $actor->role!=='superadmin')throw new DomainException('Xodimni faqat superadministrator qo‘shadi.');
        $current=$target?->role??'unassigned';
        if($actor->role!=='superadmin'){
            if($target && $target->isAdmin())throw new DomainException('Administrator hisoblarini faqat superadministrator boshqaradi.');
            if($requested!==null && $requested!==$current)throw new DomainException('Rollarni biriktirish va olib tashlash faqat superadministratorga ruxsat etilgan.');
            return $current;
        }
        $role=$requested??$current;
        if(!isset(User::ROLES[$role]))throw new DomainException('Rol noto‘g‘ri.');
        return $role;
    }
}
