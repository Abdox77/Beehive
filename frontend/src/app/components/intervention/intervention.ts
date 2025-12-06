import { Component, EventEmitter, Input, Output, ChangeDetectorRef, NgZone } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';

const BACKEND_URL = 'http://localhost:8000';

export interface InterventionData {
    id: number;
    note: string;
    createdAt: {
        date: string;
        timezone_type: number;
        timezone: string;
    };
    hiveId: number;
}

@Component({
  selector: 'app-intervention',
  imports: [CommonModule, FormsModule],
  templateUrl: './intervention.html',
  styleUrl: './intervention.css'
})
export class Intervention {
    @Input() hiveId: number = 0;
    @Output() closePopup = new EventEmitter<void>();

    showAddForm: boolean = false;
    newInterventionDescription: string = '';
    interventions: InterventionData[] = [];

    constructor (
        private http: HttpClient,
        private cdr: ChangeDetectorRef,
        private ngZone: NgZone
    ) { }

    ngOnInit() {
        this.loadInterventions();
    }

    loadInterventions() {
        const token = localStorage.getItem('token');
        this.http.get<{ success: boolean; interventions: InterventionData[] }>(`${BACKEND_URL}/api/intervention/${this.hiveId}`, {
            headers: { Authorization: `Bearer ${token}` }
        }).subscribe({
            next: (response) => {
                this.interventions = response.interventions;
                this.cdr.detectChanges();
            },
            error: (error) => console.error('Error loading interventions:', error)
        });
    }

    onAddInterventionClick() {
        console.log('Button clicked! Current state:', this.showAddForm);
        this.ngZone.run(() => {
            this.showAddForm = true;
            console.log('New state:', this.showAddForm);
        });
    }

    onCancelAdd() {
        console.log('Canceling...');
        this.ngZone.run(() => {
            this.showAddForm = false;
            this.newInterventionDescription = '';
        });
    }

    onSaveIntervention() {
        if (!this.newInterventionDescription.trim()) {
            return;
        }

        const data = {
            note: this.newInterventionDescription
        };

        const token = localStorage.getItem('token');
        this.http.post(`${BACKEND_URL}/api/intervention/${this.hiveId}`, data, {
            headers: { Authorization: `Bearer ${token}` }
        }).subscribe({
            next: () => {
                this.showAddForm = false;
                this.newInterventionDescription = '';
                this.loadInterventions();
            },
            error: (error) => console.error('Error saving intervention:', error)
        });
    }

    onClose() {
        this.closePopup.emit();
    }
}
