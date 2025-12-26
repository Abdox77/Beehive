import { Component, ViewContainerRef, Output, EventEmitter, AfterViewInit, NgZone, ChangeDetectorRef } from '@angular/core';
import * as L from 'leaflet';
import { HttpClient } from '@angular/common/http';
import { Intervention, InterventionData } from '../intervention/intervention';
import { HarvestComponent, HarvestCacheData } from '../harvest/harvest';
import { CommonModule } from '@angular/common';

interface Hive {
    id: number;
    name: string;
    lat: number;
    lng: number;
}

const BACKEND_URL = 'http://localhost:8000';

@Component({
  selector: 'app-map',
  standalone: true,
  imports: [CommonModule, Intervention, HarvestComponent],
  templateUrl: './map.html',
  styleUrl: './map.css'
})
export class MapComponent implements AfterViewInit {
    private map: L.Map | null = null;
    private markers: L.Marker[] = [];
    private hives: Hive[] = [];
    private draggedHive: Hive | null = null;
    private dragGhost: HTMLElement | null = null;
    private lastDragX: number = 0;
    private lastDragY: number = 0;
    
    showHarvestDialog: boolean = false;
    showInterventionDialog: boolean = false;
    selectedHiveId: number | null = null;
    
    private interventionsCache: { [key: number]: InterventionData[] } = {};
    private harvestsCache: { [key: number]: HarvestCacheData } = {};
    
    cachedInterventions: InterventionData[] | null = null;
    cachedHarvests: HarvestCacheData | null = null;
    
    @Output() hivesLoaded = new EventEmitter<Hive[]>();
    @Output() hiveDroppedOnTrash = new EventEmitter<Hive>();

    constructor(
        private http: HttpClient,
        private vrc: ViewContainerRef,
        private ngZone: NgZone,
        private cdr: ChangeDetectorRef
    ) { }

    ngOnInit(): void {
        this.loadHives();
    }

    ngAfterViewInit(): void {
        this.initMap();
    }

    private initMap(): void {
        this.map = L.map('map').setView([46.6000, 1.888334], 6);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(this.map);
    }

    private loadHives(): void {
        const token = localStorage.getItem('token');
        this.http.get<{ success: boolean; hives: Hive[] }>(`${BACKEND_URL}/api/hive`, {
            headers: { Authorization: `Bearer ${token}` }
        }).subscribe({
            next: (response) => {
                this.hives = response.hives;
                this.hivesLoaded.emit(this.hives);
                if (this.map) {
                    this.addHiveMarkers();
                }
            }
        });
    }

    private addHiveMarkers(): void {
        if (!this.map) return;
        
        this.markers.forEach(marker => this.map!.removeLayer(marker));
        this.markers = [];

        const hiveIcon = L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-yellow.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        this.hives.forEach(hive => {
            const marker = L.marker([hive.lat, hive.lng], { 
                icon: hiveIcon,
                draggable: true 
            }).addTo(this.map!);

            const originalLatLng = marker.getLatLng();

            marker.on('mouseover', () => {
                this.preloadHiveData(hive.id);
            });

            marker.on('dragstart', () => {
                this.ngZone.run(() => {
                    this.draggedHive = hive;
                    this.createDragGhost(hive);
                    const el = marker.getElement();
                    if (el) el.style.opacity = '0.5';
                });
            });

            marker.on('drag', (e: any) => {
                const containerPoint = this.map!.latLngToContainerPoint(e.latlng);
                const mapContainer = this.map!.getContainer().getBoundingClientRect();
                
                const markerScreenX = mapContainer.left + containerPoint.x;
                const markerScreenY = mapContainer.top + containerPoint.y;
                
                this.lastDragX = markerScreenX;
                this.lastDragY = markerScreenY;
                
                if (this.dragGhost) {
                    this.dragGhost.style.left = (markerScreenX - 12) + 'px';
                    this.dragGhost.style.top = (markerScreenY - 41) + 'px';
                }
                
                this.checkTrashBinHover(markerScreenX, markerScreenY);
            });

            marker.on('dragend', () => {
                this.ngZone.run(() => {
                    const el = marker.getElement();
                    if (el) el.style.opacity = '1';
                    
                    const markerScreenX = this.lastDragX;
                    const markerScreenY = this.lastDragY;
                    
                    const isOver = this.isOverTrashBin(markerScreenX, markerScreenY);
                    
                    if (isOver && this.draggedHive) {
                        this.hiveDroppedOnTrash.emit(this.draggedHive);
                    }
                    
                    this.resetTrashBinHighlight();
                    marker.setLatLng(originalLatLng);
                    
                    this.removeDragGhost();
                    this.draggedHive = null;
                    this.lastDragX = 0;
                    this.lastDragY = 0;
                });
            });
            
            marker.on('click', () => {
                this.ngZone.run(() => {
                    this.openHiveDialogInternal(hive.id);
                });
            });

            this.markers.push(marker);
        });

        if (this.markers.length === 1) {
            const hive = this.hives[0];
            this.map!.setView([hive.lat, hive.lng], 12);
        } else if (this.markers.length > 1) {
            const group = L.featureGroup(this.markers);
            this.map!.fitBounds(group.getBounds().pad(0.1), {
                maxZoom: 14
            });
        }
    }

    public openHiveDialog(hiveId: number): void {
        this.preloadHiveData(hiveId);
        this.openHiveDialogInternal(hiveId);
        const hive = this.hives.find(h => h.id === hiveId);
        if (hive && this.map) {
            this.map.setView([hive.lat, hive.lng], 12);
        }
    }

    private openHiveDialogInternal(hiveId: number): void {
        this.selectedHiveId = hiveId;
        if (this.interventionsCache[hiveId]) {
            this.cachedInterventions = this.interventionsCache[hiveId];
        } else {
            this.cachedInterventions = null;
        }
        this.showInterventionDialog = true;
        this.showHarvestDialog = false;
        this.cdr.detectChanges();
    }

    private preloadHiveData(hiveId: number): void {
        const token = localStorage.getItem('token');
        
        if (!this.interventionsCache[hiveId]) {
            this.http.get<any>(`${BACKEND_URL}/api/intervention/${hiveId}`, {
                headers: { Authorization: `Bearer ${token}` }
            }).subscribe({
                next: (response) => {
                    if (response.success) {
                        this.interventionsCache[hiveId] = response.interventions;
                    }
                }
            });
        }
        
        if (!this.harvestsCache[hiveId]) {
            this.http.get<any>(`${BACKEND_URL}/api/hive/${hiveId}/harvests`, {
                headers: { Authorization: `Bearer ${token}` }
            }).subscribe({
                next: (response) => {
                    if (response.success) {
                        this.harvestsCache[hiveId] = {
                            harvests: response.harvests,
                            totalWeightKg: response.totalWeightKg
                        };
                    }
                }
            });
        }
    }

    onOpenHarvest() {
        if (this.selectedHiveId && this.harvestsCache[this.selectedHiveId]) {
            this.cachedHarvests = this.harvestsCache[this.selectedHiveId];
        } else {
            this.cachedHarvests = null;
        }
        this.showInterventionDialog = false;
        this.showHarvestDialog = true;
    }

    onBackToIntervention() {
        if (this.selectedHiveId && this.interventionsCache[this.selectedHiveId]) {
            this.cachedInterventions = this.interventionsCache[this.selectedHiveId];
        }
        this.showHarvestDialog = false;
        this.showInterventionDialog = true;
    }

    onCloseDialog() {
        this.showHarvestDialog = false;
        this.showInterventionDialog = false;
        this.selectedHiveId = null;
        this.cachedInterventions = null;
        this.cachedHarvests = null;
    }

    onInterventionDataUpdated(interventions: InterventionData[]) {
        if (this.selectedHiveId) {
            this.interventionsCache[this.selectedHiveId] = interventions;
            this.cachedInterventions = interventions;
        }
    }

    onHarvestDataUpdated(data: HarvestCacheData) {
        if (this.selectedHiveId) {
            this.harvestsCache[this.selectedHiveId] = data;
            this.cachedHarvests = data;
        }
    }

    public refreshHives(): void {
        this.interventionsCache = {};
        this.harvestsCache = {};
        this.loadHives();
    }

    private createDragGhost(hive: Hive): void {
        this.dragGhost = document.createElement('div');
        this.dragGhost.className = 'fixed pointer-events-none z-[10000]';
        this.dragGhost.innerHTML = `
            <img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-yellow.png" 
                 style="width: 25px; height: 41px; opacity: 0.8;" />
        `;
        document.body.appendChild(this.dragGhost);
    }

    private removeDragGhost(): void {
        if (this.dragGhost) {
            this.dragGhost.remove();
            this.dragGhost = null;
        }
    }
    
    private checkTrashBinHover(x: number, y: number): void {
        const trashBin = document.querySelector('.trash-bin');
        if (trashBin) {
            const rect = trashBin.getBoundingClientRect();
            const isOver = x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;
            if (isOver) {
                trashBin.classList.add('trash-hover');
            } else {
                trashBin.classList.remove('trash-hover');
            }
        }
    }

    private isOverTrashBin(x: number, y: number): boolean {
        const trashBin = document.querySelector('.trash-bin');
        if (trashBin) {
            const rect = trashBin.getBoundingClientRect();
            const isOver = x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;
            return isOver;
        }
        return false;
    }

    private resetTrashBinHighlight(): void {
        const trashBin = document.querySelector('.trash-bin');
        if (trashBin) {
            trashBin.classList.remove('trash-hover');
        }
    }
}
