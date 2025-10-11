import { HttpInterceptorFn } from '@angular/common/http';
import { catchError, throwError } from 'rxjs';
import { AuthService } from '../services/auth.service';
import { Router } from '@angular/router';
import { inject } from '@angular/core';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
    const router = inject(Router);
    const authService = inject(AuthService);
    const token = localStorage.getItem('token');
    if (token) {
        const authReq = req.clone({
            headers: req.headers.set('Authorization', `Bearer ${token}`)
        });

        return next(authReq).pipe(
            catchError(error => {
                if (error.status == 401) {
                    authService.logout();
                    router.navigate(['/auth/login']);
                }
                return throwError(() => error);
            })
        );
    }
    return next(req);
};
