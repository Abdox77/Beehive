import { Component, EventEmitter, Input, Output, OnInit, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';

const BACKEND_URL = 'http://localhost:8000';

interface Harvest {
  id: number;
  date: string;
  weightG: number;
}

export interface HarvestCacheData {
  harvests: Harvest[];
  totalWeightKg: number;
}

@Component({
  selector: 'app-harvest',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './harvest.html',
  styleUrl: './harvest.css'
})
export class HarvestComponent implements OnInit, OnChanges {
  @Input() hiveId: number = 0;
  @Input() cachedData: HarvestCacheData | null = null;
  @Output() close = new EventEmitter<void>();
  @Output() backToIntervention = new EventEmitter<void>();
  @Output() dataUpdated = new EventEmitter<HarvestCacheData>();

  harvests: Harvest[] = [];
  totalWeightKg: number = 0;
  showAddForm: boolean = false;
  newHarvestDate: string = '';
  newHarvestWeight: number = 0;
  loading: boolean = false;
  isLoading: boolean = true;

  constructor(private http: HttpClient) {}

  ngOnInit() {
    this.setDefaultDate();
    if (this.cachedData) {
      this.harvests = this.cachedData.harvests;
      this.totalWeightKg = this.cachedData.totalWeightKg;
      this.isLoading = false;
    } else {
      this.loadHarvests();
    }
  }

  ngOnChanges(changes: SimpleChanges) {
    if (changes['cachedData'] && this.cachedData) {
      this.harvests = this.cachedData.harvests;
      this.totalWeightKg = this.cachedData.totalWeightKg;
      this.isLoading = false;
    }
  }

  setDefaultDate() {
    const today = new Date();
    this.newHarvestDate = today.toISOString().split('T')[0];
  }

  loadHarvests() {
    this.isLoading = true;
    const token = localStorage.getItem('token');
    this.http.get<any>(`${BACKEND_URL}/api/hive/${this.hiveId}/harvests`, {
      headers: { Authorization: `Bearer ${token}` }
    }).subscribe({
      next: (response) => {
        if (response.success) {
          this.harvests = response.harvests;
          this.totalWeightKg = response.totalWeightKg;
          this.dataUpdated.emit({
            harvests: this.harvests,
            totalWeightKg: this.totalWeightKg
          });
        }
        this.isLoading = false;
      }
    });
  }

  onAddHarvestClick() {
    this.showAddForm = true;
  }

  onCancelAdd() {
    this.showAddForm = false;
    this.newHarvestDate = '';
    this.newHarvestWeight = 0;
    this.setDefaultDate();
  }

  onSaveHarvest() {
    if (!this.newHarvestDate || this.newHarvestWeight <= 0) {
      return;
    }

    this.loading = true;
    const token = localStorage.getItem('token');
    
    this.http.post<any>(`${BACKEND_URL}/api/hive/${this.hiveId}/harvest`, {
      date: this.newHarvestDate,
      weightG: this.newHarvestWeight
    }, {
      headers: { Authorization: `Bearer ${token}` }
    }).subscribe({
      next: (response) => {
        if (response.success) {
          this.loadHarvests();
          this.onCancelAdd();
        }
        this.loading = false;
      }
    });
  }

  onClose() {
    this.close.emit();
  }

  onBackClick() {
    this.backToIntervention.emit();
  }

  getChartData() {
    const monthlyData: { [key: string]: number } = {};
    
    this.harvests.forEach(harvest => {
      const date = new Date(harvest.date);
      const monthKey = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
      
      if (!monthlyData[monthKey]) {
        monthlyData[monthKey] = 0;
      }
      monthlyData[monthKey] += harvest.weightG;
    });

    return Object.entries(monthlyData).map(([month, weight]) => ({
      month,
      weight
    })).sort((a, b) => a.month.localeCompare(b.month));
  }

  getMaxWeight(): number {
    const chartData = this.getChartData();
    if (chartData.length === 0) return 1000;
    return Math.max(...chartData.map(d => d.weight), 1000);
  }
}
