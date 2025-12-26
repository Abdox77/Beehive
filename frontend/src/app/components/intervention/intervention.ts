import { Component, EventEmitter, Input, Output, OnInit, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';

const BACKEND_URL = 'http://localhost:8000';

export interface InterventionData {
  id: number;
  note: string;
  createdAt: {
    date: string;
  };
}

@Component({
  selector: 'app-intervention',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './intervention.html',
  styleUrl: './intervention.css'
})
export class Intervention implements OnInit, OnChanges {
  @Input() hiveId: number = 0;
  @Input() cachedData: InterventionData[] | null = null;
  @Output() closePopup = new EventEmitter<void>();
  @Output() openHarvest = new EventEmitter<void>();
  @Output() dataLoaded = new EventEmitter<void>();
  @Output() dataUpdated = new EventEmitter<InterventionData[]>();

  interventions: InterventionData[] = [];
  showAddForm: boolean = false;
  newInterventionDescription: string = '';
  isLoading: boolean = true;

  constructor(private http: HttpClient) {}

  ngOnInit() {
    if (this.cachedData) {
      this.interventions = this.cachedData;
      this.isLoading = false;
      this.dataLoaded.emit();
    } else {
      this.loadInterventions();
    }
  }

  ngOnChanges(changes: SimpleChanges) {
    if (changes['cachedData'] && this.cachedData) {
      this.interventions = this.cachedData;
      this.isLoading = false;
    }
  }

  loadInterventions() {
    this.isLoading = true;
    const token = localStorage.getItem('token');
    this.http.get<any>(`${BACKEND_URL}/api/intervention/${this.hiveId}`, {
      headers: { Authorization: `Bearer ${token}` }
    }).subscribe({
      next: (response) => {
        if (response.success) {
          this.interventions = response.interventions;
          this.dataUpdated.emit(this.interventions);
        }
        this.isLoading = false;
        this.dataLoaded.emit();
      }
    });
  }

  onAddInterventionClick() {
    this.showAddForm = true;
  }

  onCancelAdd() {
    this.showAddForm = false;
    this.newInterventionDescription = '';
  }

  onSaveIntervention() {
    if (!this.newInterventionDescription.trim()) {
      return;
    }

    const token = localStorage.getItem('token');
    this.http.post<any>(`${BACKEND_URL}/api/intervention/${this.hiveId}`, {
      note: this.newInterventionDescription
    }, {
      headers: { Authorization: `Bearer ${token}` }
    }).subscribe({
      next: (response) => {
        if (response.success) {
          this.loadInterventions();
          this.onCancelAdd();
        }
      }
    });
  }

  private emitDataUpdate() {
    this.dataUpdated.emit(this.interventions);
  }

  onClose() {
    this.closePopup.emit();
  }

  onProductionClick() {
    this.openHarvest.emit();
  }
}
