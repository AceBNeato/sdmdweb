<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Equipment Details</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Equipment Information</h5>
                    </div>
                    <div class="card-body" id="equipmentContent">
                        <div class="text-center py-4" id="loadingState">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <p class="mb-0">Loading equipment details...</p>
                        </div>
                    </div>
                    <div class="card-footer text-center small text-muted">
                        Scanned via SDMD QR code
                    </div>
                </div>

                <div class="mt-3 text-center">
                    <a href="/" class="btn btn-outline-secondary btn-sm">Back to site</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const params = new URLSearchParams(window.location.search);
            const equipmentId = params.get('equipment_id');
            const content = document.getElementById('equipmentContent');
            const loading = document.getElementById('loadingState');

            if (!equipmentId) {
                loading.remove();
                content.innerHTML = '<div class="alert alert-warning mb-0">'
                    + '<h6 class="alert-heading">No equipment specified</h6>'
                    + '<p class="mb-0">The QR code did not include an equipment ID.</p>'
                    + '</div>';
                return;
            }

            fetch('{{ route('public.equipment.scan') }}?equipment_id=' + encodeURIComponent(equipmentId), {
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function(data) {
                    loading.remove();

                    if (!data.success || !data.equipment) {
                        content.innerHTML = '<div class="alert alert-danger mb-0">'
                            + '<h6 class="alert-heading">Equipment not found</h6>'
                            + '<p class="mb-0">This QR code is not linked to a valid equipment record.</p>'
                            + '</div>';
                        return;
                    }

                    const eq = data.equipment;
                    content.innerHTML = '
                        <div class="mb-3 text-center">
                            <h4 class="mb-1">' + (eq.model_number || 'Unknown Model') + '</h4>
                            <p class="text-muted mb-0">Serial: ' + (eq.serial_number || 'N/A') + '</p>
                        </div>
                        <hr>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Equipment Type</dt>
                            <dd class="col-sm-8">' + (eq.equipment_type || 'Unknown') + '</dd>

                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8"><span class="badge bg-secondary">' + (eq.status || 'Unknown') + '</span></dd>

                            <dt class="col-sm-4">Office</dt>
                            <dd class="col-sm-8">' + (eq.office || 'N/A') + '</dd>
                        </dl>
                    ';
                })
                .catch(function(err) {
                    console.error('Public equipment scan failed:', err);
                    loading.remove();
                    content.innerHTML = '<div class="alert alert-danger mb-0">'
                        + '<h6 class="alert-heading">Error loading equipment</h6>'
                        + '<p class="mb-0">There was a problem loading equipment details. Please try again later.</p>'
                        + '</div>';
                });
        })();
    </script>
</body>
</html>
