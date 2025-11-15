<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Repositories\User\UserRepository;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\VerificationCode\VerificationCodeRepository;
use App\Repositories\VerificationCode\VerificationCodeRepositoryInterface;
use App\Repositories\Complaint\ComplaintRepository;
use App\Repositories\Complaint\ComplaintRepositoryInterface;
use App\Repositories\ComplaintStatusHistory\ComplaintStatusHistoryRepository;
use App\Repositories\ComplaintStatusHistory\ComplaintStatusHistoryRepositoryInterface;
use App\Repositories\Department\DepartmentRepository;
use App\Repositories\Department\DepartmentRepositoryInterface;
use App\Repositories\ComplaintCategory\ComplaintCategoryRepository;
use App\Repositories\ComplaintCategory\ComplaintCategoryRepositoryInterface;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        $this->app->bind(
            VerificationCodeRepositoryInterface::class,
            VerificationCodeRepository::class
        );

        $this->app->bind(
            ComplaintRepositoryInterface::class,
            ComplaintRepository::class
        );

        $this->app->bind(
            ComplaintStatusHistoryRepositoryInterface::class,
            ComplaintStatusHistoryRepository::class
        );

        $this->app->bind(
            DepartmentRepositoryInterface::class,
            DepartmentRepository::class
        );

        $this->app->bind(
            ComplaintCategoryRepositoryInterface::class,
            ComplaintCategoryRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}
