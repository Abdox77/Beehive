import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from '../../components/navbar/navbar';
import { AuthService } from '../../auth/services/auth.service';
import { Route, Router } from '@angular/router';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, NavbarComponent],
  templateUrl: './dashboard.html',
  styleUrls: ['./dashboard.css']
})
export class DashboardComponent {

    constructor(
        private authService: AuthService,
        private router: Router
    ) { }

    onAddHive() {
        console.log('Add Hive clicked');
    }

    onThemeToggle() {
        document.documentElement.classList.toggle('dark');
    }

    onOpenSettings() {
        console.log('Open settings');
    }

    onLogout() {
        console.log('was clicked');
        this.authService.logout();
        this.router.navigate(['/login']);
    }
}
