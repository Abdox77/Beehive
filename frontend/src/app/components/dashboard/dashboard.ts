import { Component, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NavbarComponent } from '../../components/navbar/navbar';
import { MapComponent } from '../../components/map/map';
import { AddHiveModelComponent } from '../add-hive-model-component/add-hive-model-component';
import { HiveListComponent } from '../hive-list-component/hive-list-component';
import { TrashBinComponent } from '../trash-bin-component/trash-bin-component';
import { AuthService } from '../../auth/services/auth.service';
import { Router } from '@angular/router';
import { HttpClient } from '@angular/common/http';

const BACKEND_URL = 'http://localhost:8000';

interface Hive {
    id: number;
    name: string;
    lat: number;
    lng: number;
}

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [ CommonModule, NavbarComponent, MapComponent, AddHiveModelComponent, HiveListComponent, TrashBinComponent ],
  templateUrl: './dashboard.html',
  styleUrls: ['./dashboard.css']
})
export class DashboardComponent {
    showAddHiveModal = false;
    hives: Hive[] = [];

    @ViewChild(MapComponent) mapComponent?: MapComponent;
    @ViewChild('trashBin') trashBinComponent?: TrashBinComponent;

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
        const token = localStorage.getItem('token');
        this.http.post(`${BACKEND_URL}/api/hive`, hiveData, {
            headers: { Authorization: `Bearer ${token}` }
        }).subscribe({
            next: (response) => {
                this.showAddHiveModal = false;
                this.mapComponent?.refreshHives();
            }
        });
    }

    onHiveDeleted(hiveId: number) {
        this.mapComponent?.refreshHives();
    }

    onHivesLoaded(hives: Hive[]) {
        this.hives = hives;
    }

    onHiveDroppedOnTrash(hive: Hive) {
        if (this.trashBinComponent) {
            this.trashBinComponent.onHiveDroppedFromMap(hive);
        }
    }

    onHiveSelected(hive: Hive) {
        if (this.mapComponent) {
            this.mapComponent.openHiveDialog(hive.id);
        }
    }

    onLogout() {
        this.authService.logout();
        this.router.navigate(['/login']);
    }
}
