import { Component, ViewContainerRef, ComponentRef, Output, EventEmitter } from '@angular/core';
import * as L from 'leaflet';
import { HttpClient } from '@angular/common/http';
import { Intervention } from '../intervention/intervention';

interface Hive {
    id: number;
    name: string;
    lat: number;
    lng: number;
}

const BACKEND_URL = 'http://localhost:8000';

@Component({
  selector: 'app-map',
  imports: [],
  templateUrl: './map.html',
  styleUrl: './map.css'
})
export class Map {
    private map: any;
    private markers: L.Marker[] = [];
    private hives: Hive[] = [];
    private draggedHive: Hive | null = null;
    private dragGhost: HTMLElement | null = null;
    private lastDragX: number = 0;
    private lastDragY: number = 0;
    
    @Output() hivesLoaded = new EventEmitter<Hive[]>();
    @Output() hiveDroppedOnTrash = new EventEmitter<Hive>();

    constructor(
        private http: HttpClient,
        private vrc: ViewContainerRef
    ) { }

    ngOnInit(): void {
        this.loadHives();
    }

    ngAfterViewInit(): void {
        this.initMap();
    }

    private initMap(): void {
        this.map = L.map('map').setView([49.845732, 3.262939], 10);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(this.map);

        if (this.hives.length > 0) {
            this.addHiveMarkers();
        }
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
            },
            error: (error) => console.error('Error loading hives: ', error)
        });
    }

    private addHiveMarkers(): void {
        this.markers.forEach(marker => this.map.removeLayer(marker));
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
            }).addTo(this.map);

            const originalLatLng = marker.getLatLng();

            marker.on('dragstart', () => {
                this.draggedHive = hive;
                this.createDragGhost(hive);
                const el = marker.getElement();
                if (el) el.style.opacity = '0.5';
            });

            marker.on('drag', (e: any) => {
                const containerPoint = this.map.latLngToContainerPoint(e.latlng);
                const mapContainer = this.map.getContainer().getBoundingClientRect();
                
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

            marker.on('dragend', (e: any) => {
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

            let componentRef: ComponentRef<Intervention> | null = null;
            marker.bindPopup(() => {
                componentRef = this.vrc.createComponent(Intervention);
                componentRef.instance.hiveId = hive.id;
                componentRef.instance.closePopup.subscribe(() => {
                    marker.closePopup();
                });
                componentRef.changeDetectorRef.detectChanges();
                setTimeout(() => {
                    componentRef?.changeDetectorRef.detectChanges();
                }, 0);
                return componentRef.location.nativeElement;
            },
            {
                minWidth: 550,
                maxWidth: 550,
                offset: [0, -20],
                closeButton: false
            });

            marker.on('popupclose', () => {
                if (componentRef) {
                    componentRef.destroy();
                    componentRef = null;
                }
            });
            
            this.markers.push(marker);
        });

        if (this.markers.length > 0) {
            const group = L.featureGroup(this.markers);
            this.map.fitBounds(group.getBounds().pad(0.1));
        }
    }

    private checkTrashBinHover(x: number, y: number): void {
        const trashBin = document.querySelector('app-trash-bin');
        if (trashBin) {
            const isOver = this.isOverTrashBin(x, y);
            trashBin.dispatchEvent(new CustomEvent('markerDragOver', { 
                detail: { isOver } 
            }));
        }
    }

    private isOverTrashBin(x: number, y: number): boolean {
        const elementsAtPoint = document.elementsFromPoint(x, y);
        
        const isOverTrash = elementsAtPoint.some(el => {
            return el.tagName.toLowerCase() === 'app-trash-bin' || 
                   el.closest('app-trash-bin') !== null;
        });
        
        const trashBin = document.querySelector('app-trash-bin');
        if (!trashBin) {
            return isOverTrash;
        }
        
        const trashRect = trashBin.getBoundingClientRect();
        
        const isOverByRect = x >= trashRect.left - 50 && 
                             x <= trashRect.right + 50 &&
                             y >= trashRect.top - 50 && 
                             y <= trashRect.bottom + 50;
        
        return isOverTrash || isOverByRect;
    }

    private resetTrashBinHighlight(): void {
        const trashBin = document.querySelector('app-trash-bin');
        if (trashBin) {
            trashBin.dispatchEvent(new CustomEvent('markerDragOver', { 
                detail: { isOver: false } 
            }));
        }
    }

    public refreshHives(): void {
        this.loadHives();
    }

    public getHives(): Hive[] {
        return this.hives;
    }

    private createDragGhost(hive: Hive): void {
        this.dragGhost = document.createElement('div');
        this.dragGhost.innerHTML = `
            <img src="https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-yellow.png" 
                 style="width: 25px; height: 41px;" />
            <span style="position: absolute; left: 30px; top: 10px; background: white; padding: 2px 6px; border-radius: 4px; font-size: 12px; white-space: nowrap; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                ${hive.name}
            </span>
        `;
        this.dragGhost.style.cssText = `
            position: fixed;
            pointer-events: none;
            z-index: 10000;
            opacity: 0.9;
        `;
        document.body.appendChild(this.dragGhost);
    }

    private removeDragGhost(): void {
        if (this.dragGhost) {
            document.body.removeChild(this.dragGhost);
            this.dragGhost = null;
        }
    }
}
