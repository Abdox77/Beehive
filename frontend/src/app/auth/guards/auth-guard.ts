import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

export const authGuard = () => {
    const authService = inject(AuthService);
    const router = inject(Router);

    if (authService.isLoggerdIn === true)
    {
        console.log(`The user is logged in`);
        return true;
    }
    console.log(`You're being redirected to /auth/login`);
    return router.parseUrl('/auth/login');
};
