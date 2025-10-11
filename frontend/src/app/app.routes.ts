import { Routes } from '@angular/router';

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
        path: '**', redirectTo: '/dashboard'
    }
    // {
    //     path: 'dashboard',
    //     loadComponent: () => import('./pages/dashboard/dashboard.module').then(m => m.DashboardModule), 
    //      canActivate: [authGuard]
    // }

];



