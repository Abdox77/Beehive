import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from '../../components/navbar/navbar';
import { Map } from '../../components/map/map';
import { AddHiveModelComponent } from '../add-hive-model-component/add-hive-model-component';
import { AuthService } from '../../auth/services/auth.service';
import { Router } from '@angular/router';
import { HttpClient } from '@angular/common/http';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [ CommonModule, NavbarComponent, Map, AddHiveModelComponent ],
  templateUrl: './dashboard.html',
  styleUrls: ['./dashboard.css']
})
export class DashboardComponent {
    showAddHiveModal = false;

    constructor(
        private authService: AuthService,
        private router: Router,
        private http: HttpClient
    ) { }

    onAddHive() {
        this.showAddHiveModal = true;
    }

    
    onCloseModal() {
        this.showAddHiveModal = false;
    }

    onHiveAdded(hiveData: any) {
        // const token = localStorage.getItem('token');
        // this.http.post('http://localhost:8000/api/hive', hiveData, {
        //     headers: { Authorization: `Bearer ${token}` }
        // }).subscribe({
        //     next: (response) => {
        //         console.log('Hive created: ' , response);
        //         this.showAddHiveModal = false;
        //     },
        //     error: (error) => {
        //         console.error('Error creating hive: ', error);
        //     }
        // });
    }

    onThemeToggle() {
        document.documentElement.classList.toggle('dark');
    }

    onOpenSettings() {
        console.log('Open settings');
    }

    onLogout() {
        this.authService.logout();
        this.router.navigate(['/auth/login']);
    }
}
