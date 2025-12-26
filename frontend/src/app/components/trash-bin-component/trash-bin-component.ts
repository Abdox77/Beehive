import { Component, EventEmitter, Output, ElementRef, OnInit, OnDestroy, NgZone, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';

const BACKEND_URL = 'http://localhost:8000';

@Component({
    selector: 'app-trash-bin',
    standalone: true,
    imports: [CommonModule],
    templateUrl: './trash-bin-component.html',
    styleUrl: './trash-bin-component.css'
})
export class TrashBinComponent implements OnInit, OnDestroy {
    @Output() hiveDeleted = new EventEmitter<number>();
    
    isDragOver = false;
    showDeleteConfirm = false;
    hiveToDelete: any = null;

    private markerDragOverHandler = (e: Event) => {
        const customEvent = e as CustomEvent;
        this.ngZone.run(() => {
            this.isDragOver = customEvent.detail.isOver;
            this.cdr.detectChanges();
        });
    };

    constructor(
        private http: HttpClient, 
        private elementRef: ElementRef,
        private ngZone: NgZone,
        private cdr: ChangeDetectorRef
    ) {}

    ngOnInit(): void {
        this.elementRef.nativeElement.addEventListener('markerDragOver', this.markerDragOverHandler);
    }

    ngOnDestroy(): void {
        this.elementRef.nativeElement.removeEventListener('markerDragOver', this.markerDragOverHandler);
    }

    onHiveDroppedFromMap(hive: any): void {
        this.ngZone.run(() => {
            this.hiveToDelete = hive;
            this.showDeleteConfirm = true;
            this.isDragOver = false;
            this.cdr.detectChanges();
        });
    }

    onDragOver(event: DragEvent): void {
        event.preventDefault();
        event.dataTransfer!.dropEffect = 'move';
        this.isDragOver = true;
    }

    onDragLeave(event: DragEvent): void {
        this.isDragOver = false;
    }

    onDrop(event: DragEvent): void {
        event.preventDefault();
        this.isDragOver = false;

        const hiveData = event.dataTransfer!.getData('application/json');
        if (hiveData) {
            this.hiveToDelete = JSON.parse(hiveData);
            this.showDeleteConfirm = true;
        }
    }

    confirmDelete(): void {
        if (!this.hiveToDelete) return;

        const token = localStorage.getItem('token');
        this.http.delete(`${BACKEND_URL}/api/hive/${this.hiveToDelete.id}`, {
            headers: { Authorization: `Bearer ${token}` }
        }).subscribe({
            next: () => {
                this.hiveDeleted.emit(this.hiveToDelete.id);
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
}
