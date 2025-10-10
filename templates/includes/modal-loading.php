<?php
/**
 * Template page for modal loading view
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-body">
                <div class="spinner-loading d-flex justify-content-center">
                    <output class="spinner-border">
                        <span class="visually-hidden">Loading...</span>
                    </output>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
