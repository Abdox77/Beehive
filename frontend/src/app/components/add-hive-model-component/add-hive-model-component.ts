import { Component, EventEmitter, Input, Output, OnInit, OnDestroy, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import * as L from 'leaflet';

@Component({
  selector: 'app-add-hive-modal',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './add-hive-model-component.html',
  styleUrl: './add-hive-model-component.css'
})
export class AddHiveModelComponent implements OnInit, OnDestroy, OnChanges {
    @Input() isOpen = false;
    @Output() close = new EventEmitter<void>();
    @Output() hiveAdded = new EventEmitter<any>();

    loading = false;
    hiveForm: FormGroup;

    private map: any;
    private marker: any;
    selectedLocation: { lat: number, lng: number } | null = null;

    constructor(private formBuilder: FormBuilder) {
        this.hiveForm = this.formBuilder.group({
            name: ['', Validators.required],
            lat: [{ value: '', disabled: true }, [Validators.required, Validators.min(-90), Validators.max(90)]],
            lng: [{ value: '', disabled: true }, [Validators.required, Validators.min(-180), Validators.max(180)]]
        });
    }

    ngOnInit() {
        if (this.isOpen) {
            setTimeout(() => this.initMap(), 100);
        }
    }

    ngOnDestroy() {
        if (this.map) {
            this.map.remove();
            this.map = null;
        }
    }

    ngOnChanges(changes: SimpleChanges) {
        if (changes['isOpen'] && changes['isOpen'].currentValue && !changes['isOpen'].previousValue) {
            setTimeout(() => this.initMap(), 100);
        }
    }

    private initMap(): void {
        if (this.map) {
            this.map.invalidateSize();
            return;
        }

        this.map = L.map('addHiveMap').setView([46.6000, 1.888888], 6);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(this.map);

        this.map.on('click', (e: L.LeafletMouseEvent) => {
            this.onMapClick(e);
        });

        setTimeout(() => {
            this.map.invalidateSize();
        }, 100);
    }

    private onMapClick(e: L.LeafletMouseEvent): void {
        const { lat, lng } = e.latlng;
        if (this.marker) {
            this.map.removeLayer(this.marker);
        }

        const icon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-gold.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        this.marker = L.marker([lat, lng], { icon }).addTo(this.map);
        this.selectedLocation = { lat, lng };
        this.hiveForm.patchValue({
            lat: lat.toFixed(6),
            lng: lng.toFixed(6)
        });
    }
    
    onSubmit() {
        if (this.hiveForm.invalid || !this.selectedLocation) {
            return;
        }

        this.loading = true;
        const hiveData = {
            name: this.hiveForm.get('name')?.value,
            lat: this.selectedLocation.lat,
            lng: this.selectedLocation.lng,
        };
        
        this.hiveAdded.emit(hiveData);
    }

    onClose() {
        this.hiveForm.reset();
        this.selectedLocation = null;
        if(this.marker) {
            this.map.removeLayer(this.marker);
            this.marker = null;
        }
        this.close.emit();
    }
}
