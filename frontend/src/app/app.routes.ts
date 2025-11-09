import { Routes } from '@angular/router';
import { authGuard } from './auth/guards/auth-guard';


export const routes: Routes = [ 
    { 
        path: 'auth',
        loadChildren: () => import('./auth/auth.module').then(m => m.AuthModule)
    },
    {
        path: '',
        redirectTo: '/dashboard',
        pathMatch: 'full'
    },
    {
        path: 'dashboard',
        canMatch: [authGuard],
        loadChildren: () => import('./components/dashboard/dashboard-module').then(m => m.DashboardModule),
        runGuardsAndResolvers: 'always'
    },
    { path: '**', redirectTo: '/dashboard' }
];
