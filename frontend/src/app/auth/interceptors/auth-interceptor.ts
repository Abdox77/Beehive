import { HttpInterceptorFn } from '@angular/common/http';
import { catchError, throwError, timeout } from 'rxjs';
import { AuthService } from '../services/auth.service';
import { Router } from '@angular/router';
import { inject } from '@angular/core';

const REQUEST_TIMEOUT = 10000;

export const authInterceptor: HttpInterceptorFn = (req, next) => {
    const router = inject(Router);
    const authService = inject(AuthService);
    const token = localStorage.getItem('token');
    
    let request = req;
    if (token) {
        request = req.clone({
            headers: req.headers.set('Authorization', `Bearer ${token}`)
        });
    }

    return next(request).pipe(
        timeout(REQUEST_TIMEOUT),
        catchError(error => {
            if (error.name === 'TimeoutError') {
                return throwError(() => ({
                    status: 0,
                    error: { message: 'Request timed out. Please check if the server is running.' }
                }));
            }
            if (error.status === 0) {
                return throwError(() => ({
                    status: 0,
                    error: { message: 'Cannot connect to server. Please check if the backend is running.' }
                }));
            }
            if (error.status === 401) {
                authService.logout();
                router.navigate(['/auth/login']);
            }
            return throwError(() => error);
        })
    );
};
