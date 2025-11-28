<!-- Equipment Settings Tab -->
<div class="tab-content" id="equipment-tab">
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card settings-stat-card bg-primary text-white">
                <div class="card-body text-center">
                    <div class="h3 text-white">{{ \App\Models\EquipmentType::count() }}</div>
                    <p class="mb-0">Equipment Types</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card settings-stat-card bg-success text-white">
                <div class="card-body text-center">
                    <div class="h3 text-white">{{ \App\Models\Equipment::count() }}</div>
                    <p class="mb-0">Total Equipment</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card settings-stat-card bg-warning text-white">
                <div class="card-body text-center">
                    <div class="h3 text-white">{{ \App\Models\Category::count() }}</div>
                    <p class="mb-0">Categories</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card settings-stat-card bg-info text-white">
                <div class="card-body text-center">
                    <div class="h3 text-white">{{ \App\Models\Campus::count() }}</div>
                    <p class="mb-0">Campuses</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card settings-card">
                <div class="card-body settings-card-body text-center">
                    <i class="fas fa-tags fa-3x text-primary mb-3"></i>
                    <h5>Categories</h5>
                    <p class="text-muted">Manage equipment categories</p>
                    <a href="{{ route('admin.settings.system.categories.index') }}" class="btn btn-primary">
                        Manage Categories
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-cogs fa-3x text-success mb-3"></i>
                    <h5>Equipment Types</h5>
                    <p class="text-muted">Manage equipment types and ordering</p>
                    <a href="{{ route('admin.settings.system.equipment-types.index') }}" class="btn btn-primary">
                        Manage Equipment Types
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
