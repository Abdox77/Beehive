import { Component, EventEmitter, Input, Output } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';

interface Hive {
    id: number;
    name: string;
    lat: number;
    lng: number;
}

const BACKEND_URL = 'http://localhost:8000';

@Component({
    selector: 'app-hive-list',
    standalone: true,
    imports: [CommonModule],
    templateUrl: './hive-list-component.html',
    styleUrl: './hive-list-component.css'
})
export class HiveListComponent {
    @Input() hives: Hive[] = [];
    @Output() hiveDeleted = new EventEmitter<number>();
    @Output() hiveSelected = new EventEmitter<Hive>();

    showDeleteConfirm = false;
    hiveToDelete: Hive | null = null;

    constructor(private http: HttpClient) {}

    onDeleteClick(hive: Hive, event: Event): void {
        event.stopPropagation();
        this.hiveToDelete = hive;
        this.showDeleteConfirm = true;
    }

    confirmDelete(): void {
        if (!this.hiveToDelete) return;

        const token = localStorage.getItem('token');
        this.http.delete(`${BACKEND_URL}/api/hive/${this.hiveToDelete.id}`, {
            headers: { Authorization: `Bearer ${token}` }
        }).subscribe({
            next: () => {
                this.hiveDeleted.emit(this.hiveToDelete!.id);
                this.closeDeleteConfirm();
            }
        });
    }

    cancelDelete(): void {
        this.closeDeleteConfirm();
    }

    private closeDeleteConfirm(): void {
        this.showDeleteConfirm = false;
        this.hiveToDelete = null;
    }

    onDragStart(event: DragEvent, hive: Hive): void {
        event.dataTransfer!.effectAllowed = 'move';
        event.dataTransfer!.setData('application/json', JSON.stringify(hive));
    }
}
