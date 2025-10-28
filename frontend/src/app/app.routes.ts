import { Routes } from '@angular/router';
import { authGuard } from './auth/guards/auth-guard';


export const routes: Routes = [ 
    { 
        path: 'auth',
        loadChildren: () => import('./auth/auth-module').then(m => m.AuthModule)
    },
    {
        path: '',
        redirectTo: '/dashboard',
        pathMatch: 'full'
    },
    {
        path: 'dashboard',
        loadComponent: () => import('./pages/dashboard/dashboard-module').then(m => m.DashboardModule), 
        canActivate: [authGuard]
    },
    {
        path: '**', redirectTo: '/dashboard'
    }
];
