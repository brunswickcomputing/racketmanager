<div class="row justify-content-center">
  <div class="col-12 col-md-9">
    <h1><?php _e( 'My favourites', 'racketmanager' ); ?></h1>
    <div>
      <!-- Nav tabs -->
      <ul class="nav nav-tabs frontend" id="myTab" role="tablist">
        <?php $i = 0;
        foreach ( $favouriteTypes AS $favouriteType ) { ?>
          <li class="nav-item" role="presentation">
            <button class="nav-link <?php if ( $i == 0 ) { echo 'active'; } ?>" id="favouriteType-<?php echo $favouriteType['name'] ?>-tab" data-bs-toggle="pill" data-bs-target="#favouriteType-<?php echo $favouriteType['name'] ?>" type="button" role="tab" aria-controls="favouriteType-<?php echo $favouriteType['name'] ?>" aria-selected="true"><?php echo $favouriteType['name'] ?></button>
          </li>
          <?php $i ++;
          } ?>
      </ul>
      <!-- Tab panes -->
      <div class="tab-content">
        <?php $i = 0;
        foreach ( $favouriteTypes AS $favouriteType ) { ?>
      	<div class="tab-pane fade <?php if ( $i == 0 ) { echo 'show active'; } ?>" id="favouriteType-<?php echo $favouriteType['name'] ?>" role="tabpanel" aria-labelledby="favouriteType-<?php echo $favouriteType['name'] ?>-tab">
          <?php foreach ($favouriteType['favourites'] as $key => $favourite) { ?>
            <div>
              <h4 class="header"><a href="/<?php echo $favouriteType['name'] ?>s/<?php echo seoUrl($favourite->name) ?>"><?php echo $favourite->name ?></a></h4>
              <?php if ( is_user_logged_in() ) {
                $isFavourite = $racketmanager->userFavourite($favouriteType['name'], $favourite->id); ?>
                <div class="fav-icon">
                  <a href="" id="fav-<?php echo $favourite->id ?>" title="<?php if ( $isFavourite) { _e( 'Remove favourite', 'racketmanager' ); } else { _e( 'Add favourite', 'racketmanager'); } ?>" data-js="add-favourite" data-type="<?php echo $favouriteType['name'] ?>" data-favourite="<?php echo $favourite->id ?>">
                    <i class="fav-icon-svg racketmanager-svg-icon <?php if ( $isFavourite ) { echo 'fav-icon-svg-selected'; } ?>">
                      <?php racketmanager_the_svg('icon-star') ?>
                    </i>
                  </a>
                  <div class="fav-msg" id="fav-msg-<?php echo $favourite->id ?>"></div>
                </div>
              <?php } ?>
            </div>
          <?php } ?>
      	</div>
        <?php $i ++;
        } ?>
      </div>
    </div>
  </div>
</div>
